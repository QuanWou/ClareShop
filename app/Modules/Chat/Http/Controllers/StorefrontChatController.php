<?php

namespace App\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Actions\BootstrapChatAction;
use App\Modules\Chat\Actions\SendChatMessageAction;
use App\Modules\Chat\Http\Requests\StoreChatMessageRequest;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Models\ChatMessage;
use App\Modules\Chat\Support\ChatMessagePresenter;
use App\Modules\Chat\Support\ChatSettingsRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StorefrontChatController extends Controller
{
    public function bootstrap(
        Request $request,
        BootstrapChatAction $action,
        ChatMessagePresenter $presenter,
        ChatSettingsRegistry $settings,
    ): JsonResponse {
        $conversation = $action->execute($request->user());
        $after = max(0, $request->integer('after'));

        return response()->json($this->payload($conversation, $presenter, $settings, $after));
    }

    public function store(
        StoreChatMessageRequest $request,
        SendChatMessageAction $action,
        ChatMessagePresenter $presenter,
        ChatSettingsRegistry $settings,
    ): JsonResponse {
        $conversation = $action->execute(
            $request->user(),
            trim($request->string('message')->toString()),
            $request->string('client_message_id')->toString(),
            productId: $request->integer('product_id') ?: null,
        );

        return response()->json($this->payload($conversation, $presenter, $settings, 0));
    }

    /** @return array<string, mixed> */
    private function payload(
        ChatConversation $conversation,
        ChatMessagePresenter $presenter,
        ChatSettingsRegistry $settings,
        int $after,
    ): array {
        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->where('id', '>', $after)
            ->latest('id')
            ->limit(50)
            ->get()
            ->sortBy('id')
            ->values();

        $conversation->messages()
            ->whereIn('sender_type', [ChatMessage::SENDER_BOT, ChatMessage::SENDER_ADMIN, ChatMessage::SENDER_SYSTEM])
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return [
            'conversation' => [
                'status' => $conversation->status,
                'status_label' => $this->statusLabel($conversation->status),
            ],
            'assistant_name' => $settings->get('assistant_name'),
            'messages' => $messages->map(fn (ChatMessage $message): array => $presenter->present($message))->all(),
            'quick_actions' => [
                ['label' => 'Theo dõi đơn', 'message' => 'Đơn gần nhất của tôi tới đâu rồi?'],
                ['label' => 'Tìm sản phẩm', 'message' => 'Gợi ý đèn ngủ đang còn hàng'],
                ['label' => 'Thanh toán', 'message' => 'Tôi thanh toán chưa?'],
                ['label' => 'Gặp nhân viên', 'message' => 'Tôi muốn gặp nhân viên hỗ trợ'],
            ],
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            ChatConversation::STATUS_WAITING_ADMIN => 'Đang chờ nhân viên',
            ChatConversation::STATUS_ADMIN => 'Nhân viên đang hỗ trợ',
            ChatConversation::STATUS_CLOSED => 'Đã đóng',
            default => 'Trợ lý trực tuyến',
        };
    }
}
