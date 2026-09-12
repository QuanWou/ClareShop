<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Models\ChatMessage;
use App\Modules\Chat\Support\GeminiService;
use App\Modules\Orders\Models\Order;
use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Promotions\Models\UserVoucher;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_conversation_and_welcome_message_persist_across_refresh(): void
    {
        $first = $this->getJson(route('chat.bootstrap'))->assertOk();
        $first->assertJsonPath('conversation.status', 'bot');
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseCount('chat_messages', 1);

        $this->getJson(route('chat.bootstrap'))->assertOk();
        $this->assertDatabaseCount('chat_conversations', 1);
        $this->assertDatabaseCount('chat_messages', 1);
    }

    public function test_smalltalk_and_product_queries_are_local_and_never_call_gemini(): void
    {
        $this->seed(CatalogSeeder::class);
        Http::fake();

        $this->postJson(route('chat.messages.store'), [
            'message' => 'Xin chào',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonFragment(['text' => 'Xin chào 👋 Mình có thể giúp bạn tìm đèn, kiểm tra đơn hàng hoặc hỗ trợ thanh toán.']);

        $productResponse = $this->postJson(route('chat.messages.store'), [
            'message' => 'Gợi ý đèn ngủ đang còn hàng',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonFragment(['type' => 'product_card']);

        $productResponse->assertJsonMissing(['stock_label' => 'Tạm hết hàng']);

        Http::assertNothingSent();
    }

    public function test_product_analysis_uses_gemini_with_real_context_and_displays_the_product_name(): void
    {
        $this->seed(CatalogSeeder::class);
        config(['chat.gemini.keys' => ['backend-only-key']]);
        Cache::flush();
        $product = Product::query()->published()->firstOrFail();
        $naturalReply = $product->name.' phù hợp với một góc nghỉ cần ánh sáng dịu. Mức giá và lượng hàng hiện tại cũng khá dễ cân nhắc trước khi chọn màu.';
        Http::fake(fn () => Http::response([
            'candidates' => [['content' => ['parts' => [['text' => $naturalReply]]], 'finishReason' => 'STOP']],
        ]));

        $analysisResponse = $this->postJson(route('chat.messages.store'), [
            'message' => 'Phân tích sản phẩm '.$product->name,
            'product_id' => $product->getKey(),
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk();
        $analysisMessage = collect($analysisResponse->json('messages'))
            ->filter(fn (array $message): bool => $message['type'] === 'product_card')
            ->last();

        $this->assertSame($naturalReply, $analysisMessage['text']);
        $this->assertSame($product->getKey(), data_get($analysisMessage, 'metadata.products.0.id'));
        $this->assertDatabaseHas('chat_messages', [
            'sender_type' => ChatMessage::SENDER_CUSTOMER,
            'message' => 'Phân tích sản phẩm '.$product->name,
        ]);
        $this->assertDatabaseMissing('chat_messages', ['message' => 'Phân tích sản phẩm #'.$product->getKey()]);
        Http::assertSent(function (Request $request) use ($product): bool {
            $prompt = (string) data_get($request->data(), 'contents.0.parts.0.text');

            return str_contains($prompt, '"name":"'.$product->name.'"')
                && str_contains($prompt, '"stock_quantity":');
        });
        $this->get(route('catalog.products.show', $product))
            ->assertOk()
            ->assertSee('Nhờ Clare phân tích mẫu này')
            ->assertSee('data-chat-analyze-product="'.$product->getKey().'"', false)
            ->assertSee('data-chat-analyze-name="'.$product->name.'"', false);
    }

    public function test_customer_cannot_read_another_customers_order_through_chat(): void
    {
        $owner = User::factory()->create(['is_active' => true]);
        $other = User::factory()->create(['is_active' => true]);
        $order = $this->orderFor($owner, 'CLR-CHAT-PRIVATE');
        Http::fake();

        $response = $this->actingAs($other)->postJson(route('chat.messages.store'), [
            'message' => 'Xem đơn '.$order->number,
            'client_message_id' => (string) Str::uuid(),
        ]);

        $response->assertOk()->assertJsonFragment([
            'text' => 'Mình không tìm thấy đơn hàng này trong tài khoản của bạn.',
        ]);
        $response->assertJsonMissing(['total' => '450.000 VND']);
        Http::assertNothingSent();
    }

    public function test_guest_order_query_does_not_expose_private_data_or_call_gemini(): void
    {
        Http::fake();
        $this->postJson(route('chat.messages.store'), [
            'message' => 'Đơn hàng của tôi đang ở đâu?',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonFragment([
            'text' => 'Bạn cần đăng nhập để mình kiểm tra đơn hàng riêng của bạn.',
        ]);
        Http::assertNothingSent();
    }

    public function test_handoff_admin_takeover_reply_return_to_bot_and_close(): void
    {
        $customer = User::factory()->create(['is_active' => true]);
        $admin = User::factory()->create(['is_active' => true, 'role' => 'admin']);

        $this->actingAs($customer)->postJson(route('chat.messages.store'), [
            'message' => 'Tôi muốn gặp nhân viên hỗ trợ',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonPath('conversation.status', 'waiting_admin');

        $conversation = ChatConversation::query()->firstOrFail();
        $this->actingAs($admin)->postJson(route('admin.chat.takeover', $conversation))->assertOk();
        $this->assertSame('admin', $conversation->fresh()->status);

        $this->actingAs($admin)->postJson(route('admin.chat.reply', $conversation), ['message' => 'Clare đang hỗ trợ bạn đây.'])->assertOk();
        $this->assertDatabaseHas('chat_messages', ['sender_type' => 'admin', 'message' => 'Clare đang hỗ trợ bạn đây.']);

        $this->actingAs($admin)->postJson(route('admin.chat.return-to-bot', $conversation))->assertOk();
        $this->assertSame('bot', $conversation->fresh()->status);

        $this->actingAs($admin)->postJson(route('admin.chat.close', $conversation))->assertOk();
        $this->assertSame('closed', $conversation->fresh()->status);
        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->getKey(),
            'sender_type' => 'system',
            'message' => 'Cuộc trò chuyện đã đóng. Bạn có thể gửi tin mới để bắt đầu lại.',
        ]);

        $this->actingAs($customer)->postJson(route('chat.messages.store'), [
            'message' => 'Xin chào',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonPath('conversation.status', 'bot');
    }

    public function test_payment_and_voucher_questions_use_real_local_data_without_gemini(): void
    {
        $customer = User::factory()->create(['is_active' => true]);
        $order = $this->orderFor($customer, 'CLR-CHAT-PAID');
        $promotion = PromotionCode::query()->create([
            'code' => 'CHAT10',
            'name' => 'Voucher chat',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'minimum_order_amount' => 100000,
            'per_user_usage_limit' => 1,
            'is_active' => true,
            'is_public' => true,
            'requires_claim' => true,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
        ]);
        UserVoucher::query()->create([
            'user_id' => $customer->getKey(),
            'promotion_code_id' => $promotion->getKey(),
            'used_count' => 0,
            'claimed_at' => now(),
        ]);
        Http::fake();

        $this->actingAs($customer)->postJson(route('chat.messages.store'), [
            'message' => 'Tôi thanh toán chưa?',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonFragment(['text' => "Đơn {$order->number} đã thanh toán thành công."]);

        $this->actingAs($customer)->postJson(route('chat.messages.store'), [
            'message' => 'Tôi có voucher nào còn dùng được?',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonFragment(['code' => 'CHAT10']);

        Http::assertNothingSent();
    }

    public function test_external_question_calls_gemini_through_laravel_backend(): void
    {
        config(['chat.gemini.keys' => ['backend-only-key']]);
        Cache::flush();
        Http::fake(fn (Request $request) => Http::response([
            'candidates' => [[
                'content' => ['parts' => [
                    ['text' => 'Mặt Trời cách Trái Đất khoảng 150'],
                    ['text' => ' triệu km.'],
                ]],
                'finishReason' => 'STOP',
            ]],
        ]));

        $this->postJson(route('chat.messages.store'), [
            'message' => 'Mặt trời cách trái đất bao xa?',
            'client_message_id' => (string) Str::uuid(),
        ])->assertOk()->assertJsonFragment(['text' => 'Mặt Trời cách Trái Đất khoảng 150 triệu km.']);

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'backend-only-key')
            && data_get($request->data(), 'generationConfig.thinkingConfig.thinkingBudget') === 0
            && data_get($request->data(), 'generationConfig.maxOutputTokens') === 700);
    }

    public function test_repeated_client_message_id_does_not_create_duplicate_messages(): void
    {
        $clientMessageId = (string) Str::uuid();
        $payload = ['message' => 'Xin chào', 'client_message_id' => $clientMessageId];

        $this->postJson(route('chat.messages.store'), $payload)->assertOk();
        $this->postJson(route('chat.messages.store'), $payload)->assertOk();

        $this->assertSame(1, ChatMessage::query()->where('client_message_id', $clientMessageId)->count());
        $this->assertDatabaseCount('chat_messages', 3);
    }

    public function test_gemini_keys_rotate_round_robin_and_never_appear_in_storefront(): void
    {
        config(['chat.gemini.keys' => ['secret-key-1', 'secret-key-2', 'secret-key-3', 'secret-key-4', 'secret-key-5']]);
        Cache::flush();
        $used = [];
        Http::fake(function (Request $request) use (&$used) {
            $used[] = $request->header('x-goog-api-key')[0] ?? null;

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Câu trả lời ngắn.']]]]]]);
        });
        $conversation = ChatConversation::query()->create(['guest_session_id' => (string) Str::uuid(), 'status' => 'bot']);
        $conversation->messages()->create(['sender_type' => ChatMessage::SENDER_CUSTOMER, 'message' => 'Mặt trời cách Trái Đất bao xa?']);

        for ($i = 0; $i < 6; $i++) {
            $this->assertSame('Câu trả lời ngắn.', app(GeminiService::class)->answer('Câu hỏi', $conversation));
        }

        $this->assertSame(['secret-key-1', 'secret-key-2', 'secret-key-3', 'secret-key-4', 'secret-key-5', 'secret-key-1'], $used);
        $this->get(route('catalog.home'))->assertOk()->assertDontSee('secret-key-1');
    }

    public function test_rate_limited_gemini_key_enters_cooldown_and_failover_uses_next_key(): void
    {
        config(['chat.gemini.keys' => ['key-one', 'key-two', 'key-three']]);
        Cache::flush();
        $used = [];
        Http::fake(function (Request $request) use (&$used) {
            $key = $request->header('x-goog-api-key')[0] ?? '';
            $used[] = $key;

            return $key === 'key-one'
                ? Http::response(['error' => ['status' => 'RESOURCE_EXHAUSTED']], 429)
                : Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Đã chuyển key.']]]]]]);
        });
        $conversation = ChatConversation::query()->create(['guest_session_id' => (string) Str::uuid(), 'status' => 'bot']);

        $this->assertSame('Đã chuyển key.', app(GeminiService::class)->answer('Tokyo thuộc nước nào?', $conversation));
        Cache::put('chat:gemini:key-cursor', 2);
        $this->assertSame('Đã chuyển key.', app(GeminiService::class)->answer('Câu tiếp theo', $conversation));

        $this->assertSame(['key-one', 'key-two', 'key-two'], $used);
        $this->assertTrue(Cache::has('chat:gemini:key:0:cooldown'));

        $this->travel(121)->seconds();
        Cache::put('chat:gemini:key-cursor', 2);
        $this->assertSame('Đã chuyển key.', app(GeminiService::class)->answer('Sau thời gian chờ', $conversation));
        $this->assertSame(['key-one', 'key-two', 'key-two', 'key-one', 'key-two'], $used);
    }

    private function orderFor(User $customer, string $number): Order
    {
        return Order::query()->create([
            'number' => $number,
            'user_id' => $customer->getKey(),
            'status' => 'shipped',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
            'currency' => 'VND',
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '0901234567',
            'shipping_recipient_name' => $customer->name,
            'shipping_phone' => '0901234567',
            'shipping_address_line_1' => '12 Nguyễn Huệ',
            'shipping_ward' => 'Bến Nghé',
            'shipping_district' => 'Quận 1',
            'shipping_city' => 'Hồ Chí Minh',
            'subtotal' => 420000,
            'shipping_fee' => 30000,
            'discount_total' => 0,
            'total' => 450000,
            'placed_at' => now(),
        ]);
    }
}
