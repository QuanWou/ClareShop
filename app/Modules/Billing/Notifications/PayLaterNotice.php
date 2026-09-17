<?php

namespace App\Modules\Billing\Notifications;

use App\Modules\Billing\Models\PayLaterPurchase;
use App\Modules\Settings\Actions\ConfigureStoreMailAction;
use App\Modules\Shared\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PayLaterNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PayLaterPurchase $purchase,
        public readonly string $eventKey,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        app(ConfigureStoreMailAction::class)->execute();
        $content = $this->content();

        return (new MailMessage)
            ->subject($content['title'])
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line($content['message'])
            ->line('Mã đơn: '.$this->purchase->order->number)
            ->line('Số tiền phải trả: '.Money::formatVnd($this->purchase->amount_due))
            ->line('Ngày đến hạn: '.$this->purchase->due_at->format('d/m/Y'))
            ->action('Thanh toán ngay', route('account.pay-later.pay', $this->purchase))
            ->line('Clare chỉ nhắc bạn thanh toán và không tự động trừ tiền từ bất kỳ tài khoản nào.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            ...$this->content(),
            'order_number' => $this->purchase->order->number,
            'amount_due' => (int) $this->purchase->amount_due,
            'due_at' => $this->purchase->due_at->toIso8601String(),
            'status' => $this->purchase->status,
            'action_url' => route('account.pay-later.pay', $this->purchase),
            'action_label' => 'Thanh toán ngay',
        ];
    }

    private function content(): array
    {
        return match ($this->eventKey) {
            'month_before' => ['title' => 'Khoản trả sau còn 1 tháng đến hạn', 'message' => 'Khoản mua trước, trả sau của bạn sẽ đến hạn sau một tháng.'],
            'seven_days_before' => ['title' => 'Khoản trả sau còn 7 ngày đến hạn', 'message' => 'Vui lòng chuẩn bị phương thức thanh toán cho khoản trả sau sắp đến hạn.'],
            'due' => ['title' => 'Khoản trả sau đã đến hạn', 'message' => 'Khoản trả sau đã đến hạn. Hãy chọn phương thức và xác nhận thanh toán.'],
            'overdue' => ['title' => 'Khoản trả sau đang quá hạn', 'message' => 'Khoản trả sau đã quá hạn và vẫn đang chờ bạn thanh toán.'],
            'paid' => ['title' => 'Thanh toán khoản trả sau thành công', 'message' => 'Clare đã ghi nhận khoản trả sau của bạn được thanh toán đầy đủ.'],
            'failed' => ['title' => 'Thanh toán khoản trả sau chưa thành công', 'message' => 'Lần thanh toán gần nhất chưa thành công. Bạn có thể chọn phương thức khác và thử lại.'],
            default => ['title' => 'Cập nhật khoản mua trước, trả sau', 'message' => 'Trạng thái khoản trả sau của bạn vừa được cập nhật.'],
        };
    }
}
