<?php

namespace App\Modules\Chat\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Chat\Actions\UpdateChatSettingsAction;
use App\Modules\Chat\Http\Requests\AdminChatReplyRequest;
use App\Modules\Chat\Http\Requests\UpdateChatSettingsRequest;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Models\ChatMessage;
use App\Modules\Chat\Support\ChatMessagePresenter;
use App\Modules\Chat\Support\ChatSettingsRegistry;
use App\Modules\Chat\Support\GeminiKeyPool;
use App\Modules\Shared\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminChatController extends Controller
{
    public function index(ChatSettingsRegistry $settings, GeminiKeyPool $keyPool): View
    {
        return view('admin.chat.index', [
            'settings' => $settings,
            'geminiKeys' => $keyPool->statuses(),
        ]);
    }

    public function conversations(Request $request): JsonResponse
    {
        $filter = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());
        $query = ChatConversation::query()
            ->with(['customer:id,name,email,phone', 'assignedAdmin:id,name'])
            ->withCount(['messages as unread_count' => fn ($message) => $message
                ->where('sender_type', ChatMessage::SENDER_CUSTOMER)
                ->where('is_read', false)])
            ->with(['messages' => fn ($message) => $message->latest('id')->limit(1)]);

        if (in_array($filter, ['bot', 'waiting_admin', 'admin', 'closed'], true)) {
            $query->where('status', $filter);
        }
        if ($search !== '') {
            $query->whereHas('customer', fn ($customer) => $customer
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%"));
        }

        $rows = $query->latest('last_message_at')->limit(80)->get()->map(fn (ChatConversation $conversation): array => [
            'id' => $conversation->getKey(),
            'customer' => $conversation->customer?->name ?? 'Khách vãng lai',
            'email' => $conversation->customer?->email,
            'status' => $conversation->status,
            'status_label' => $this->statusLabel($conversation->status),
            'assigned_admin' => $conversation->assignedAdmin?->name,
            'unread' => $conversation->unread_count,
            'last_message' => mb_substr((string) $conversation->messages->first()?->message, 0, 90),
            'time' => $conversation->last_message_at?->diffForHumans(),
        ]);

        return response()->json(['conversations' => $rows]);
    }

    public function show(ChatConversation $conversation, ChatMessagePresenter $presenter): JsonResponse
    {
        $conversation->messages()
            ->where('sender_type', ChatMessage::SENDER_CUSTOMER)
            ->where('is_read', false)
            ->update(['is_read' => true]);
        $conversation->load(['customer:id,name,email,phone', 'assignedAdmin:id,name']);
        $messages = $conversation->messages()->with('sender:id,name')->latest('id')->limit(100)->get()->sortBy('id')->values();
        $orders = $conversation->customer?->orders()
            ->latest('placed_at')
            ->limit(3)
            ->get(['id', 'number', 'status', 'payment_status', 'total', 'placed_at'])
            ->map(fn ($order): array => [
                'number' => $order->number,
                'status' => $order->statusLabel(),
                'payment' => $order->paymentStatusLabel(),
                'total' => Money::formatVnd($order->total),
            ])
            ->all() ?? [];

        return response()->json([
            'conversation' => [
                'id' => $conversation->getKey(),
                'status' => $conversation->status,
                'status_label' => $this->statusLabel($conversation->status),
                'assigned_admin_id' => $conversation->assigned_admin_id,
                'customer' => [
                    'name' => $conversation->customer?->name ?? 'Khách vãng lai',
                    'email' => $conversation->customer?->email,
                    'phone' => $conversation->customer?->phone,
                ],
                'orders' => $orders,
            ],
            'messages' => $messages->map(fn (ChatMessage $message): array => $presenter->present($message))->all(),
        ]);
    }

    public function takeover(Request $request, ChatConversation $conversation): JsonResponse
    {
        DB::transaction(function () use ($request, $conversation): void {
            $locked = ChatConversation::query()->lockForUpdate()->findOrFail($conversation->getKey());
            if ($locked->status === ChatConversation::STATUS_ADMIN && $locked->assigned_admin_id !== $request->user()->getKey()) {
                abort(409, 'Cuộc trò chuyện đang được nhân viên khác hỗ trợ.');
            }
            $locked->update(['status' => ChatConversation::STATUS_ADMIN, 'assigned_admin_id' => $request->user()->getKey()]);
            $locked->messages()->create([
                'sender_type' => ChatMessage::SENDER_SYSTEM,
                'message' => 'Nhân viên '.$request->user()->name.' đã tham gia cuộc trò chuyện.',
                'message_type' => 'system',
            ]);
        });

        return response()->json(['message' => 'Đã nhận cuộc trò chuyện.']);
    }

    public function reply(AdminChatReplyRequest $request, ChatConversation $conversation): JsonResponse
    {
        abort_unless(
            $conversation->status === ChatConversation::STATUS_ADMIN
            && $conversation->assigned_admin_id === $request->user()->getKey(),
            409,
            'Bạn cần nhận cuộc trò chuyện trước khi trả lời.',
        );
        $message = $conversation->messages()->create([
            'sender_type' => ChatMessage::SENDER_ADMIN,
            'sender_id' => $request->user()->getKey(),
            'message' => trim($request->string('message')->toString()),
            'message_type' => 'text',
            'is_read' => false,
        ]);
        $conversation->update(['last_message_at' => $message->created_at]);

        return response()->json(['message' => 'Đã gửi phản hồi.']);
    }

    public function returnToBot(Request $request, ChatConversation $conversation): JsonResponse
    {
        $conversation->update(['status' => ChatConversation::STATUS_BOT, 'assigned_admin_id' => null]);
        $conversation->messages()->create([
            'sender_type' => ChatMessage::SENDER_SYSTEM,
            'message' => 'Cuộc trò chuyện đã được chuyển lại cho trợ lý.',
            'message_type' => 'system',
        ]);

        return response()->json(['message' => 'Đã chuyển lại cho trợ lý.']);
    }

    public function close(Request $request, ChatConversation $conversation, ChatSettingsRegistry $settings): JsonResponse
    {
        $conversation->update(['status' => ChatConversation::STATUS_CLOSED, 'assigned_admin_id' => null]);
        $conversation->messages()->create([
            'sender_type' => ChatMessage::SENDER_SYSTEM,
            'message' => $settings->get('closed_message'),
            'message_type' => 'system',
        ]);

        return response()->json(['message' => 'Đã đóng cuộc trò chuyện.']);
    }

    public function updateSettings(UpdateChatSettingsRequest $request, UpdateChatSettingsAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('success', 'Đã lưu cấu hình chatbot.');
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            ChatConversation::STATUS_WAITING_ADMIN => 'Chờ nhân viên',
            ChatConversation::STATUS_ADMIN => 'Đang hỗ trợ',
            ChatConversation::STATUS_CLOSED => 'Đã đóng',
            default => 'Trợ lý',
        };
    }
}
