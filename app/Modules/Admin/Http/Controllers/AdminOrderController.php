<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\ListAdminOrdersAction;
use App\Modules\Admin\Actions\ShowAdminOrderAction;
use App\Modules\Admin\Http\Requests\ListAdminOrdersRequest;
use App\Modules\Admin\Http\Requests\RecordAdminPaymentStatusRequest;
use App\Modules\Admin\Http\Requests\UpdateAdminOrderStatusRequest;
use App\Modules\Orders\Actions\RecordPaymentStatusAction;
use App\Modules\Orders\Actions\SendOrderConfirmationAction;
use App\Modules\Orders\Actions\TransitionOrderStatusAction;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminOrderController extends Controller
{
    public function index(ListAdminOrdersRequest $request, ListAdminOrdersAction $listOrders): View
    {
        return view('admin.orders.index', [
            'orders' => $listOrders->execute($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function show(
        Order $order,
        ShowAdminOrderAction $showOrder,
        TransitionOrderStatusAction $transitionOrder,
        RecordPaymentStatusAction $recordPayment,
    ): View {
        $order = $showOrder->execute($order);

        return view('admin.orders.show', [
            'order' => $order,
            'nextStatuses' => $transitionOrder->allowedNextStatuses($order),
            'paymentNextStatuses' => $order->payments
                ->mapWithKeys(fn (Payment $payment) => [$payment->getKey() => collect($recordPayment->allowedNextStatuses($order, $payment))]),
        ]);
    }

    public function updateStatus(
        UpdateAdminOrderStatusRequest $request,
        Order $order,
        TransitionOrderStatusAction $transitionOrder,
    ): RedirectResponse {
        $transitionOrder->execute(
            order: $order,
            actorId: (int) $request->user()->getAuthIdentifier(),
            nextStatus: $request->validated('status'),
            note: $request->validated('admin_note'),
            cancelReason: $request->validated('cancel_reason'),
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Trạng thái đơn hàng đã được cập nhật.');
    }

    public function updatePaymentStatus(
        RecordAdminPaymentStatusRequest $request,
        Order $order,
        Payment $payment,
        RecordPaymentStatusAction $recordPayment,
    ): RedirectResponse {
        $recordPayment->execute(
            order: $order,
            payment: $payment,
            actorId: (int) $request->user()->getAuthIdentifier(),
            nextStatus: $request->validated('payment_status'),
            note: $request->validated('payment_note'),
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Trạng thái thanh toán đã được ghi nhận.');
    }

    public function resendConfirmation(
        Order $order,
        SendOrderConfirmationAction $sendConfirmation,
    ): RedirectResponse {
        if ($sendConfirmation->execute($order, force: true)) {
            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', "Đã gửi email xác nhận đến {$order->customer_email}.");
        }

        return redirect()
            ->route('admin.orders.show', $order)
            ->withErrors([
                'email' => 'Không thể gửi email. Hãy cấu hình SMTP thật trong Cài đặt và kiểm tra lại tài khoản/mật khẩu ứng dụng.',
            ]);
    }
}
