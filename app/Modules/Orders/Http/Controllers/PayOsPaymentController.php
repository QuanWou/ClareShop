<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Actions\FailPaymentAttemptAction;
use App\Modules\Orders\Actions\InitializePayOsPaymentAction;
use App\Modules\Orders\Actions\SyncPayOsPaymentAction;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PayOsPaymentController extends Controller
{
    public function retry(
        Request $request,
        Order $order,
        Payment $payment,
        InitializePayOsPaymentAction $initializePayment,
    ): RedirectResponse {
        $this->authorizePayment($request, $order, $payment);

        try {
            $initializePayment->execute($payment);

            return redirect()
                ->route('account.orders.show', $order)
                ->with('success', 'Đã tạo mã QR payOS mới. Bạn có 3 phút để thanh toán.')
                ->withFragment('payment-qr');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['payment' => 'Chưa thể tạo phiên payOS mới. Vui lòng kiểm tra cấu hình hoặc thử lại sau.']);
        }
    }

    public function returned(Request $request, SyncPayOsPaymentAction $syncPayment): RedirectResponse
    {
        $payment = $this->paymentFromOrderCode($request);
        $this->authorizeOwner($request, $payment->order);

        try {
            $payment = $syncPayment->execute($payment);
            $redirect = redirect($this->completionUrl($payment->order));

            if ($payment->status === 'paid') {
                return $redirect
                    ->with('success', 'payOS đã xác nhận thanh toán thành công.')
                    ->with('payment_success', true);
            }

            return $redirect->with('success', 'Clare đang chờ payOS xác nhận giao dịch. Trạng thái sẽ tự cập nhật.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect($this->completionUrl($payment->order))
                ->withErrors(['payment' => 'Chưa thể xác nhận payOS. Clare sẽ tiếp tục kiểm tra trạng thái giao dịch.']);
        }
    }

    public function cancelled(Request $request, FailPaymentAttemptAction $failPayment): RedirectResponse
    {
        $payment = $this->paymentFromOrderCode($request);
        $this->authorizeOwner($request, $payment->order);
        $failPayment->execute(
            payment: $payment,
            reason: 'Khách hàng đã hủy bước thanh toán trên payOS.',
            actorId: (int) $request->user()->getAuthIdentifier(),
        );

        return redirect($this->completionUrl($payment->order))
            ->withErrors(['payment' => 'Bạn đã hủy thanh toán payOS. Đơn vẫn được giữ để bạn chọn phương thức khác hoặc hủy đơn.']);
    }

    private function paymentFromOrderCode(Request $request): Payment
    {
        $orderCode = $request->query('orderCode');
        abort_unless(is_string($orderCode) && ctype_digit($orderCode), 404);

        return Payment::query()
            ->with('order')
            ->where('provider', 'payos')
            ->where('provider_reference', $orderCode)
            ->firstOrFail();
    }

    private function authorizePayment(Request $request, Order $order, Payment $payment): void
    {
        $this->authorizeOwner($request, $order);
        abort_unless((int) $payment->order_id === (int) $order->getKey() && $payment->provider === 'payos', 404);
    }

    private function authorizeOwner(Request $request, Order $order): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()?->getAuthIdentifier(), 404);
    }

    private function completionUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'checkout.complete',
            now()->addDays(7),
            ['orderNumber' => $order->number],
        );
    }
}
