<?php

namespace App\Modules\Chat\Support;

use App\Models\User;
use App\Modules\Chat\Data\ChatReply;
use App\Modules\Chat\Models\ChatConversation;
use App\Modules\Chat\Resolvers\OrderChatResolver;
use App\Modules\Chat\Resolvers\PaymentChatResolver;
use App\Modules\Chat\Resolvers\PolicyChatResolver;
use App\Modules\Chat\Resolvers\ProductChatResolver;
use App\Modules\Chat\Resolvers\VoucherChatResolver;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class ChatMessageRouter
{
    use NormalizesChatText;

    public function __construct(
        private readonly ProductChatResolver $products,
        private readonly OrderChatResolver $orders,
        private readonly PaymentChatResolver $payments,
        private readonly VoucherChatResolver $vouchers,
        private readonly PolicyChatResolver $policies,
        private readonly GeminiService $gemini,
        private readonly ChatSettingsRegistry $settings,
    ) {}

    /** @return array{reply: ChatReply, handoff: bool} */
    public function route(string $message, ?User $customer, ChatConversation $conversation, ?int $productId = null): array
    {
        if ($this->isHandoff($message)) {
            return ['reply' => new ChatReply(
                trim($this->settings->get('handoff_message').' '.$this->settings->get('admin_unavailable_message')),
                'handoff',
            ), 'handoff' => true];
        }

        if ($this->isSmalltalk($message)) {
            return ['reply' => new ChatReply($this->smalltalkReply($message)), 'handoff' => false];
        }

        if ($this->isLocalTimeQuestion($message)) {
            return ['reply' => new ChatReply('Bây giờ là '.now()->format('H:i').' ngày '.now()->format('d/m/Y').' theo giờ Việt Nam.'), 'handoff' => false];
        }

        $normalized = $this->normalize($message);
        $resolver = match (true) {
            $this->payments->supports($message) => $this->payments,
            $this->orders->supports($message) => $this->orders,
            preg_match('/voucher|ma giam|ma uu dai|khuyen mai/', $normalized) === 1 => $this->vouchers,
            $this->policies->supports($message) => $this->policies,
            $this->products->supports($message) => $this->products,
            default => null,
        };

        if ($resolver !== null) {
            $reply = $resolver instanceof ProductChatResolver
                ? $resolver->resolve($message, $customer, $conversation, $productId)
                : $resolver->resolve($message, $customer, $conversation);

            return ['reply' => $reply, 'handoff' => false];
        }

        if (! $this->settings->enabled('gemini_enabled') || ! $this->settings->enabled('external_questions_enabled')) {
            return ['reply' => new ChatReply('Mình có thể giúp bạn tìm đèn, kiểm tra đơn hàng, voucher hoặc thanh toán của Clare.'), 'handoff' => false];
        }

        $rateKey = 'chat-gemini:'.(request()->user()?->getAuthIdentifier() ?? request()->ip());
        if (RateLimiter::tooManyAttempts($rateKey, 8)) {
            return ['reply' => new ChatReply($this->settings->get('gemini_unavailable_message'), 'text', ['show_handoff' => true]), 'handoff' => false];
        }
        RateLimiter::hit($rateKey, 60);

        try {
            return ['reply' => new ChatReply($this->gemini->answer($message, $conversation)), 'handoff' => false];
        } catch (Throwable) {
            return ['reply' => new ChatReply($this->settings->get('gemini_unavailable_message'), 'text', ['show_handoff' => true]), 'handoff' => false];
        }
    }

    private function isHandoff(string $message): bool
    {
        return $this->containsAny($message, [
            'gặp nhân viên', 'gặp admin', 'người thật', 'nhân viên hỗ trợ', 'cskh',
            'hỗ trợ thanh toán', 'thanh toán giúp', 'khiếu nại',
        ]);
    }

    private function isSmalltalk(string $message): bool
    {
        return preg_match('/^(xin chao|chao|hello|hi|hey|shop oi|cam on|thanks|thank you)[!. ]*$/', $this->normalize($message)) === 1;
    }

    private function smalltalkReply(string $message): string
    {
        return str_contains($this->normalize($message), 'cam on') || str_contains($this->normalize($message), 'thank')
            ? 'Rất vui vì đã hỗ trợ được bạn. Khi cần, cứ nhắn Clare nhé.'
            : 'Xin chào 👋 Mình có thể giúp bạn tìm đèn, kiểm tra đơn hàng hoặc hỗ trợ thanh toán.';
    }

    private function isLocalTimeQuestion(string $message): bool
    {
        $normalized = $this->normalize($message);

        return preg_match('/(may gio|gio hien tai|hom nay ngay may)/', $normalized) === 1
            && ! str_contains($normalized, 'tokyo');
    }
}
