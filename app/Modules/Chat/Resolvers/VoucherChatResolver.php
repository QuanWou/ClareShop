<?php

namespace App\Modules\Chat\Resolvers;

use App\Models\User;
use App\Modules\Chat\Data\ChatReply;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Support\NormalizesChatText;
use App\Modules\Promotions\Actions\ListCustomerVouchersAction;
use App\Modules\Promotions\Models\PromotionCode;
use App\Modules\Shared\Support\Money;

class VoucherChatResolver implements WebsiteChatResolver
{
    use NormalizesChatText;

    public function __construct(private readonly ListCustomerVouchersAction $listCustomerVouchers) {}

    public function supports(string $message): bool
    {
        return $this->containsAny($message, ['voucher', 'mã giảm', 'mã ưu đãi', 'khuyến mãi', 'giảm giá']);
    }

    public function resolve(string $message, ?User $customer, ChatConversation $conversation): ChatReply
    {
        if ($customer === null) {
            return new ChatReply('Bạn có thể xem các ưu đãi công khai ngay tại Kho voucher.', 'text', [
                'action_url' => route('promotions.index'),
                'action_label' => 'Xem voucher',
            ]);
        }

        $available = $this->listCustomerVouchers->execute($customer, 'available')->take(3);
        if ($available->isEmpty()) {
            return new ChatReply('Ví của bạn chưa có voucher khả dụng. Bạn có thể xem các ưu đãi đang mở trong Kho voucher.', 'text', [
                'action_url' => route('promotions.index'),
                'action_label' => 'Xem voucher',
            ]);
        }

        $vouchers = $available->map(function (array $row): array {
            /** @var PromotionCode $promotion */
            $promotion = $row['voucher']->promotionCode;
            $offer = $promotion->discount_type === 'percentage'
                ? 'Giảm '.rtrim(rtrim((string) $promotion->discount_value, '0'), '.').'%'
                : 'Giảm '.Money::formatVnd($promotion->discount_value);

            return [
                'code' => $promotion->code,
                'offer' => $offer,
                'minimum' => $promotion->minimum_order_amount ? 'Đơn từ '.Money::formatVnd($promotion->minimum_order_amount) : 'Không yêu cầu giá trị tối thiểu',
                'expires' => $promotion->ends_at?->format('d/m/Y'),
            ];
        })->all();

        return new ChatReply('Bạn đang có '.count($vouchers).' voucher khả dụng.', 'voucher_card', [
            'vouchers' => $vouchers,
            'action_url' => route('account.vouchers.index'),
            'action_label' => 'Mở Ví voucher',
        ]);
    }
}
