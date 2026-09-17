<?php

namespace App\Modules\Billing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Billing\Actions\CreateClarePayTopUpAction;
use App\Modules\Billing\Actions\CreatePayLaterPayOsPaymentAction;
use App\Modules\Billing\Actions\DispatchPayLaterNotificationAction;
use App\Modules\Billing\Actions\PayPayLaterWithClarePayAction;
use App\Modules\Billing\Actions\PayPayLaterWithSimulatedPayPalAction;
use App\Modules\Billing\Actions\ResolveClarePayWalletAction;
use App\Modules\Billing\Actions\SyncBillingPayOsPaymentAction;
use App\Modules\Billing\Http\Requests\ClarePayTopUpRequest;
use App\Modules\Billing\Http\Requests\PayLaterPaymentRequest;
use App\Modules\Billing\Models\BillingPaymentAttempt;
use App\Modules\Billing\Models\PayLaterPurchase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerBillingController extends Controller
{
    public function showPayLater(Request $request, PayLaterPurchase $purchase, ResolveClarePayWalletAction $resolveWallet): View
    {
        $this->authorizePurchase($request, $purchase);
        $purchase->load(['order', 'attempts' => fn ($query) => $query->latest()]);

        return view('customers.billing.pay-later-payment', [
            'purchase' => $purchase,
            'wallet' => $resolveWallet->execute($this->customer($request)),
            'payOsEnabled' => (bool) config('services.payos.enabled'),
        ]);
    }

    public function payPayLater(
        PayLaterPaymentRequest $request,
        PayLaterPurchase $purchase,
        PayPayLaterWithSimulatedPayPalAction $payPal,
        PayPayLaterWithClarePayAction $clarePay,
        CreatePayLaterPayOsPaymentAction $payOs,
    ): RedirectResponse {
        $this->authorizePurchase($request, $purchase);
        $method = (string) $request->validated('payment_method');

        if ($method === 'payos') {
            try {
                $attempt = $payOs->execute($this->customer($request), $purchase);
            } catch (\Throwable $exception) {
                report($exception);

                return back()->withErrors(['payment_method' => 'Chưa thể tạo phiên PayOS. Vui lòng thử lại hoặc chọn phương thức khác.']);
            }

            return redirect()->away((string) $attempt->approval_url);
        }

        $method === 'clare_pay'
            ? $clarePay->execute($this->customer($request), $purchase)
            : $payPal->execute($this->customer($request), $purchase);

        return redirect()->route('account.pay-later.pay', $purchase)
            ->with('success', 'Khoản mua trước, trả sau đã được thanh toán thành công.');
    }

    public function topUp(ClarePayTopUpRequest $request, CreateClarePayTopUpAction $createTopUp): RedirectResponse
    {
        try {
            $attempt = $createTopUp->execute($this->customer($request), (int) $request->validated('amount'));
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors(['amount' => 'Chưa thể tạo phiên nạp tiền PayOS. Vui lòng thử lại sau.']);
        }

        return redirect()->away((string) $attempt->approval_url);
    }

    public function payOsReturn(Request $request, SyncBillingPayOsPaymentAction $sync): RedirectResponse
    {
        $attempt = $this->attemptFromRequest($request);
        $this->authorizeAttempt($request, $attempt);
        $attempt = $sync->execute($attempt);

        return $this->redirectForAttempt($attempt)
            ->with('success', $attempt->status === 'paid'
                ? 'PayOS đã xác nhận giao dịch thành công.'
                : 'Clare đang chờ webhook hoặc API PayOS xác nhận giao dịch.');
    }

    public function payOsCancel(Request $request, DispatchPayLaterNotificationAction $notify): RedirectResponse
    {
        $attempt = $this->attemptFromRequest($request);
        $this->authorizeAttempt($request, $attempt);

        if ($attempt->status !== 'paid') {
            $attempt->update(['status' => 'failed', 'failure_reason' => 'Khách hàng đã hủy trên PayOS.']);
            if ($attempt->clarePayTransaction) {
                $attempt->clarePayTransaction->update(['status' => 'failed']);
            }
            if ($attempt->payLaterPurchase) {
                $attempt->payLaterPurchase->update(['status' => 'failed', 'latest_failure_reason' => 'Giao dịch PayOS đã bị hủy.']);
                $notify->execute($attempt->payLaterPurchase->fresh(['user', 'order']), 'failed');
            }
        }

        return $this->redirectForAttempt($attempt)
            ->withErrors(['payment' => 'Giao dịch PayOS đã được hủy và không có số dư nào bị thay đổi.']);
    }

    private function attemptFromRequest(Request $request): BillingPaymentAttempt
    {
        $orderCode = $request->query('orderCode');
        abort_unless(is_string($orderCode) && ctype_digit($orderCode), 404);

        return BillingPaymentAttempt::query()
            ->with(['clarePayTransaction', 'payLaterPurchase'])
            ->where('provider', 'payos')
            ->where('provider_reference', $orderCode)
            ->firstOrFail();
    }

    private function redirectForAttempt(BillingPaymentAttempt $attempt): RedirectResponse
    {
        return $attempt->pay_later_purchase_id
            ? redirect()->route('account.pay-later.pay', $attempt->pay_later_purchase_id)
            : redirect()->route('account.show')->withFragment('clare-pay');
    }

    private function authorizePurchase(Request $request, PayLaterPurchase $purchase): void
    {
        abort_unless((int) $purchase->user_id === (int) $request->user()?->getAuthIdentifier(), 404);
    }

    private function authorizeAttempt(Request $request, BillingPaymentAttempt $attempt): void
    {
        abort_unless((int) $attempt->user_id === (int) $request->user()?->getAuthIdentifier(), 404);
    }

    private function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
