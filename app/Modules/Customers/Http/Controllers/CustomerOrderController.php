<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customers\Actions\ShowCustomerOrderAction;
use App\Modules\Customers\Http\Requests\CancelCustomerOrderRequest;
use App\Modules\Customers\Http\Requests\ChangeCustomerOrderPaymentMethodRequest;
use App\Modules\Orders\Actions\CancelCustomerOrderAction;
use App\Modules\Orders\Actions\ChangeOrderPaymentMethodAction;
use App\Modules\Orders\Actions\ResolvePayOsPaymentAction;
use App\Modules\Orders\Actions\SyncPayOsPaymentAction;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Support\PaymentMethodCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function show(
        Request $request,
        Order $order,
        ShowCustomerOrderAction $showOrder,
        ResolvePayOsPaymentAction $resolvePayOsPayment,
    ): View {
        /** @var User $user */
        $user = $request->user();
        $order = $showOrder->execute($user, $order);

        $payment = $order->payments->sortByDesc('id')->first();

        return view('customers.orders.show', [
            'order' => $order,
            'payment' => $payment,
            'paymentMethod' => PaymentMethodCatalog::get($order->payment_method),
            'paymentMethods' => PaymentMethodCatalog::all(),
            'payOs' => $resolvePayOsPayment->execute($order, $payment),
        ]);
    }

    public function changePaymentMethod(
        ChangeCustomerOrderPaymentMethodRequest $request,
        Order $order,
        ChangeOrderPaymentMethodAction $changePaymentMethod,
    ): RedirectResponse {
        /** @var User $customer */
        $customer = $request->user();

        try {
            $payment = $changePaymentMethod->execute(
                customer: $customer,
                order: $order,
                paymentMethodCode: (string) $request->validated('payment_method'),
            );
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'payment_method' => 'Đã ghi nhận phương thức mới nhưng chưa thể tạo phiên thanh toán. Bạn có thể chọn lại hoặc thử tiếp từ đơn hàng.',
            ]);
        }

        if (in_array($payment->provider, ['paypal', 'momo'], true) && filled($payment->approval_url)) {
            return redirect()->away((string) $payment->approval_url);
        }

        $message = match ($payment->provider) {
            'payos' => 'Đã đổi sang payOS và tạo mã QR mới có hiệu lực trong 3 phút.',
            'cod' => 'Đã đổi sang thanh toán khi nhận hàng.',
            'pay_later_review' => 'Đã ghi nhận lựa chọn mua trước, trả sau.',
            default => 'Đã cập nhật phương thức thanh toán.',
        };

        return redirect()
            ->route('account.orders.show', $order)
            ->with('success', $message)
            ->withFragment($payment->provider === 'payos' ? 'payment-qr' : 'payment-options');
    }

    public function cancel(
        CancelCustomerOrderRequest $request,
        Order $order,
        CancelCustomerOrderAction $cancelOrder,
    ): RedirectResponse {
        /** @var User $customer */
        $customer = $request->user();

        $cancelOrder->execute(
            customer: $customer,
            order: $order,
            reason: $request->cancellationReason(),
        );

        return redirect()
            ->route('account.orders.show', $order)
            ->with('success', 'Đơn hàng đã được hủy. Tồn kho và lượt sử dụng voucher đã được hoàn lại.');
    }

    public function paymentStatus(
        Request $request,
        Order $order,
        ShowCustomerOrderAction $showOrder,
        SyncPayOsPaymentAction $syncPayOsPayment,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $order = $showOrder->execute($user, $order);
        $payment = $order->payments->sortByDesc('id')->first();

        // A bank transfer can reach payOS after Clare's local QR timeout. Query
        // the gateway before reporting an expired state so a paid transfer is
        // reconciled even when the webhook was delayed or unavailable.
        if ($payment?->provider === 'payos'
            && in_array($payment->status, ['pending', 'unpaid', 'expired'], true)) {
            try {
                $syncPayOsPayment->execute($payment);
                $order = $showOrder->execute($user, $order);
                $payment = $order->payments->sortByDesc('id')->first();
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'data' => [
                'status' => $payment?->status ?? $order->payment_status,
                'payment_status' => $order->payment_status,
                'provider' => $payment?->provider,
            ],
        ]);
    }
}
