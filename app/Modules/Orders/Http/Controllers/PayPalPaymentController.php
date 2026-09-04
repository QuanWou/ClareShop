<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Actions\CapturePayPalPaymentAction;
use App\Modules\Orders\Actions\FailPayPalPaymentAction;
use App\Modules\Orders\Actions\InitializePayPalPaymentAction;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PayPalPaymentController extends Controller
{
    public function retry(
        Request $request,
        Order $order,
        Payment $payment,
        InitializePayPalPaymentAction $initializePayment,
    ): RedirectResponse {
        $this->authorizePayment($request, $order, $payment);
        $payment = $initializePayment->execute($payment);

        return redirect()->away((string) $payment->approval_url);
    }

    public function approved(Request $request, CapturePayPalPaymentAction $capturePayment): RedirectResponse
    {
        $payment = $this->paymentFromToken($request);

        try {
            $capturePayment->execute($payment);

            return $this->redirectToCompletion($request, $payment->order)
                ->with('success', 'PayPal đã xác nhận thanh toán thành công.')
                ->with('payment_success', true);
        } catch (\Throwable $exception) {
            report($exception);

            return $this->redirectToCompletion($request, $payment->order)
                ->withErrors(['payment' => 'Chưa thể xác nhận PayPal. Bạn có thể thử thanh toán lại từ trang đơn hàng.']);
        }
    }

    public function cancel(Request $request, FailPayPalPaymentAction $failPayment): RedirectResponse
    {
        $payment = $this->paymentFromToken($request);
        $failPayment->execute($payment, 'Khách hàng đã hủy bước phê duyệt trên PayPal.');

        return $this->redirectToCompletion($request, $payment->order)
            ->withErrors(['payment' => 'Bạn đã hủy thanh toán PayPal. Đơn vẫn được giữ để bạn thử lại.']);
    }

    private function paymentFromToken(Request $request): Payment
    {
        $token = $request->query('token');
        abort_unless(is_string($token) && $token !== '', 404);

        return Payment::query()
            ->with('order')
            ->where('provider', 'paypal')
            ->where('provider_reference', $token)
            ->firstOrFail();
    }

    private function authorizePayment(Request $request, Order $order, Payment $payment): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()?->getAuthIdentifier(), 404);
        abort_unless((int) $payment->order_id === (int) $order->getKey() && $payment->provider === 'paypal', 404);
    }

    private function completionUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'checkout.complete',
            now()->addDays(7),
            ['orderNumber' => $order->number],
        );
    }

    private function redirectToCompletion(Request $request, Order $order): RedirectResponse
    {
        if ($request->user() === null) {
            return redirect()->guest($this->completionUrl($order));
        }

        abort_unless((int) $order->user_id === (int) $request->user()->getAuthIdentifier(), 404);

        return redirect($this->completionUrl($order));
    }
}
