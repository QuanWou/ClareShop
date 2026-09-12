<?php

namespace App\Modules\Chat\Resolvers;

use App\Models\User;
use App\Modules\Chat\Data\ChatReply;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Support\NormalizesChatText;

class PolicyChatResolver implements WebsiteChatResolver
{
    use NormalizesChatText;

    public function supports(string $message): bool
    {
        return $this->containsAny($message, [
            'phí vận chuyển', 'phí ship', 'giao hàng', 'bảo hành', 'đổi trả',
            'hướng dẫn mua', 'cách mua', 'phương thức thanh toán', 'tài khoản', 'đăng nhập',
        ]);
    }

    public function resolve(string $message, ?User $customer, ChatConversation $conversation): ChatReply
    {
        if ($this->containsAny($message, ['phí vận chuyển', 'phí ship', 'giao hàng'])) {
            return new ChatReply('Phí giao hàng được ước tính tại checkout theo địa chỉ, khối lượng và đơn vị GHN, GHTK hoặc J&T bạn chọn. Tổng phí luôn được máy chủ tính lại trước khi đặt đơn.');
        }

        if ($this->containsAny($message, ['phương thức thanh toán', 'cách thanh toán'])) {
            return new ChatReply('Clare hiện hỗ trợ COD, payOS, PayPal, MoMo và trả sau. Trạng thái chuyển khoản chỉ được cập nhật khi cổng thanh toán xác nhận.');
        }

        if ($this->containsAny($message, ['bảo hành', 'đổi trả'])) {
            return new ChatReply('Clare chưa công bố thời hạn bảo hành hoặc đổi trả chung cho mọi sản phẩm. Mình có thể kết nối bạn với nhân viên để xác nhận đúng theo mẫu bạn quan tâm.', 'text', [
                'show_handoff' => true,
            ]);
        }

        if ($this->containsAny($message, ['tài khoản', 'đăng nhập'])) {
            return new ChatReply($customer
                ? 'Bạn đang đăng nhập. Từ trang Tài khoản, bạn có thể xem đơn hàng, voucher và địa chỉ đã lưu.'
                : 'Bạn cần đăng nhập để xem đơn hàng, voucher và địa chỉ cá nhân.', 'text', [
                    'action_url' => $customer ? route('account.show') : route('login'),
                    'action_label' => $customer ? 'Mở tài khoản' : 'Đăng nhập',
                ]);
        }

        return new ChatReply('Bạn chọn sản phẩm và biến thể, thêm vào giỏ, tích các món muốn mua rồi tiếp tục đến checkout. Clare sẽ tính lại tồn kho, ưu đãi và phí giao hàng trước khi tạo đơn.', 'text', [
            'action_url' => route('catalog.products.index'),
            'action_label' => 'Khám phá sản phẩm',
        ]);
    }
}
