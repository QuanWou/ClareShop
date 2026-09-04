<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Actions\FailPaymentAttemptAction;
use App\Modules\Orders\Actions\InitializeMomoPaymentAction;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class MomoPaymentController extends Controller
{
    public function retry(
        Request $request,
        Order $order,
        Payment $payment,
        InitializeMomoPaymentAction $initializePayment,
    ): RedirectResponse {
        abort_unless((int) $order->user_id === (int) $request->user()?->getAuthIdentifier(), 404);
        abort_unless((int) $payment->order_id === (int) $order->getKey() && $payment->provider === 'momo', 404);

        try {
            $payment = $initializePayment->execute($payment);

            if ($payment->status === 'paid') {
                return redirect()
                    ->route('account.orders.show', ['order' => $order, 'payment' => 'success'])
                    ->with('payment_success', true);
            }

            if (blank($payment->approval_url)) {
                return back()->withErrors(['payment' => 'MoMo chưa trả về liên kết thanh toán hợp lệ. Vui lòng thử lại sau.']);
            }

            return redirect()->away((string) $payment->approval_url);
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['payment' => 'Chưa thể tạo phiên MoMo mới. Vui lòng kiểm tra cấu hình cổng thanh toán hoặc thử lại sau.']);
        }
    }

    public function returned(Request $request, FailPaymentAttemptAction $failPayment): RedirectResponse
    {
        $orderId = $request->query('orderId');
        abort_unless(is_string($orderId) && $orderId !== '', 404);

        $payment = Payment::query()->with('order')->where('provider', 'momo')->where('provider_reference', $orderId)->firstOrFail();
        abort_unless((int) $payment->order->user_id === (int) $request->user()?->getAuthIdentifier(), 404);

        if ($request->integer('resultCode', 0) !== 0 && $payment->status !== 'paid') {
            $payment = $failPayment->execute(
                payment: $payment,
                reason: 'Khách hàng đã hủy hoặc MoMo không hoàn tất giao dịch: '.(string) $request->query('message', 'Không thành công.'),
                actorId: (int) $request->user()->getAuthIdentifier(),
            );
        }

        $redirect = redirect()->to(URL::temporarySignedRoute('checkout.complete', now()->addDays(7), ['orderNumber' => $payment->order->number]));

        if ($payment->fresh()->status === 'paid') {
            $redirect->with('payment_success', true);
        }

        if ($payment->fresh()->status === 'failed') {
            $redirect->withErrors(['payment' => 'Thanh toán MoMo chưa hoàn tất. Bạn có thể chọn phương thức khác hoặc hủy đơn.']);
        }

        return $redirect;
    }
}
