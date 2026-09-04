<?php

namespace App\Modules\Promotions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Promotions\Actions\ClaimPromotionCodeAction;
use App\Modules\Promotions\Actions\ListPublicPromotionCodesAction;
use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PromotionPageController extends Controller
{
    public function index(Request $request, ListPublicPromotionCodesAction $listPromotions): View
    {
        /** @var User|null $customer */
        $customer = $request->user();

        return view('promotions.index', [
            'voucherRows' => $listPromotions->execute($customer),
        ]);
    }

    public function claim(
        Request $request,
        PromotionCode $promotion,
        ClaimPromotionCodeAction $claimPromotion,
    ): RedirectResponse {
        /** @var User|null $customer */
        $customer = $request->user();

        if ($customer === null) {
            $request->session()->put('promotions.pending_claim_id', $promotion->getKey());

            return redirect()
                ->route('login')
                ->with('success', 'Đăng nhập để nhận voucher vào tài khoản Clare.');
        }

        $claimPromotion->execute($customer, $promotion);

        return redirect()
            ->route('promotions.index')
            ->with('success', 'Voucher đã được lưu vào Ví voucher của bạn.');
    }

    public function resume(Request $request, ClaimPromotionCodeAction $claimPromotion): RedirectResponse
    {
        $promotionId = $request->session()->pull('promotions.pending_claim_id');

        if (! is_numeric($promotionId)) {
            return redirect()->route('promotions.index');
        }

        /** @var User $customer */
        $customer = $request->user();
        $promotion = PromotionCode::query()->findOrFail((int) $promotionId);
        $claimPromotion->execute($customer, $promotion);

        return redirect()
            ->route('promotions.index')
            ->with('success', 'Voucher đã được lưu vào Ví voucher của bạn.');
    }
}
