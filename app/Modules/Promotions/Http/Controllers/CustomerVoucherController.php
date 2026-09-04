<?php

namespace App\Modules\Promotions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Promotions\Actions\ListCustomerVouchersAction;
use App\Modules\Promotions\Actions\SelectVoucherForCheckoutAction;
use App\Modules\Promotions\Models\UserVoucher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CustomerVoucherController extends Controller
{
    public function index(Request $request, ListCustomerVouchersAction $listVouchers): View
    {
        /** @var User $customer */
        $customer = $request->user();
        $filter = $request->string('filter')->toString();
        $filter = in_array($filter, ['all', 'available', 'upcoming', 'reserved', 'used', 'expired', 'unavailable'], true)
            ? $filter
            : 'all';

        return view('customers.vouchers.index', [
            'voucherRows' => $listVouchers->execute($customer, $filter),
            'filter' => $filter,
        ]);
    }

    public function useNow(
        Request $request,
        UserVoucher $voucher,
        SelectVoucherForCheckoutAction $selectVoucher,
    ): RedirectResponse {
        /** @var User $customer */
        $customer = $request->user();
        $code = $selectVoucher->execute($customer, $voucher);

        abort_if($code === '', 404);
        $request->session()->put('checkout.discount_code', $code);

        return redirect()->route('checkout.show');
    }
}
