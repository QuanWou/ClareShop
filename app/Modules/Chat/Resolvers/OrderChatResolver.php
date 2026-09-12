<?php

namespace App\Modules\Chat\Resolvers;

use App\Models\User;
use App\Modules\Chat\Data\ChatReply;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Support\ChatSettingsRegistry;
use App\Modules\Chat\Support\NormalizesChatText;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Support\Money;

class OrderChatResolver implements WebsiteChatResolver
{
    use NormalizesChatText;

    public function __construct(private readonly ChatSettingsRegistry $settings) {}

    public function supports(string $message): bool
    {
        return preg_match('/\bCLR-[A-Z0-9-]+\b/i', $message) === 1
            || preg_match('/(^|\s)don(\s|$)/', $this->normalize($message)) === 1
            || $this->containsAny($message, ['đơn hàng', 'đơn của tôi', 'đơn gần nhất', 'mã đơn', 'order', 'theo dõi đơn', 'tới đâu']);
    }

    public function resolve(string $message, ?User $customer, ChatConversation $conversation): ChatReply
    {
        if ($customer === null) {
            return new ChatReply($this->settings->get('guest_order_message'));
        }

        preg_match('/\bCLR-[A-Z0-9-]+\b/i', $message, $match);
        $number = isset($match[0]) ? strtoupper($match[0]) : null;

        $query = Order::query()
            ->where('user_id', $customer->getKey())
            ->with(['payments' => fn ($payment) => $payment->latest('id')->limit(1)]);

        $order = $number
            ? $query->where('number', $number)->first()
            : $query->latest('placed_at')->first();

        if ($order === null) {
            return new ChatReply($number
                ? 'Mình không tìm thấy đơn hàng này trong tài khoản của bạn.'
                : 'Tài khoản của bạn chưa có đơn hàng nào.');
        }

        return new ChatReply(
            "Đơn {$order->number} hiện {$this->statusInSentence($order->statusLabel())}.",
            'order_card',
            ['order' => [
                'number' => $order->number,
                'status' => $order->statusLabel(),
                'total' => Money::formatVnd($order->total),
                'payment' => $order->paymentStatusLabel(),
                'url' => route('account.orders.show', $order),
            ]],
        );
    }

    private function statusInSentence(string $label): string
    {
        return match ($label) {
            'Chờ xác nhận' => 'đang chờ xác nhận',
            'Chờ lấy hàng' => 'đang chờ lấy hàng',
            'Đang chuẩn bị giao' => 'đang được chuẩn bị để giao',
            'Đang giao hàng' => 'đang được giao',
            'Đã giao' => 'đã giao thành công',
            'Đã hủy' => 'đã hủy',
            default => mb_strtolower($label),
        };
    }
}
