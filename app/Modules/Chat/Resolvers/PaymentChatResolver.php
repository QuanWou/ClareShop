<?php

namespace App\Modules\Chat\Resolvers;

use App\Models\User;
use App\Modules\Chat\Data\ChatReply;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Support\ChatSettingsRegistry;
use App\Modules\Chat\Support\NormalizesChatText;
use App\Modules\Orders\Models\Order;

class PaymentChatResolver implements WebsiteChatResolver
{
    use NormalizesChatText;

    public function __construct(private readonly ChatSettingsRegistry $settings) {}

    public function supports(string $message): bool
    {
        return $this->containsAny($message, ['thanh toán', 'đã trả', 'payos', 'paypal', 'momo', 'chuyển khoản', 'hoàn tiền']);
    }

    public function resolve(string $message, ?User $customer, ChatConversation $conversation): ChatReply
    {
        if ($customer === null) {
            return new ChatReply($this->settings->get('guest_order_message'));
        }

        preg_match('/\bCLR-[A-Z0-9-]+\b/i', $message, $match);
        $number = isset($match[0]) ? strtoupper($match[0]) : null;
        $query = Order::query()->where('user_id', $customer->getKey());
        $order = $number ? $query->where('number', $number)->first() : $query->latest('placed_at')->first();

        if ($order === null) {
            return new ChatReply($number
                ? 'Mình không tìm thấy đơn hàng này trong tài khoản của bạn.'
                : 'Tài khoản của bạn chưa có đơn để kiểm tra thanh toán.');
        }

        $messageText = match ($order->payment_status) {
            'paid' => "Đơn {$order->number} đã thanh toán thành công.",
            'refunded' => "Khoản thanh toán của đơn {$order->number} đã được hoàn tiền.",
            'failed' => "Thanh toán của đơn {$order->number} chưa thành công. Bạn có thể chọn lại phương thức thanh toán trong trang đơn hàng.",
            'expired' => "Phiên thanh toán của đơn {$order->number} đã hết hạn. Bạn có thể tạo phiên mới trong trang đơn hàng.",
            default => "Đơn {$order->number} hiện chưa hoàn tất thanh toán.",
        };

        return new ChatReply($messageText, 'payment_status', [
            'action_url' => route('account.orders.show', $order),
            'action_label' => 'Xem đơn hàng',
        ]);
    }
}
