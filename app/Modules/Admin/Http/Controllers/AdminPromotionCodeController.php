<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\ListAdminPromotionCodesAction;
use App\Modules\Admin\Http\Requests\StoreAdminPromotionCodeRequest;
use App\Modules\Admin\Http\Requests\UpdateAdminPromotionCodeRequest;
use App\Modules\Promotions\Actions\ArchivePromotionCodeAction;
use App\Modules\Promotions\Actions\CreatePromotionCodeAction;
use App\Modules\Promotions\Actions\UpdatePromotionCodeAction;
use App\Modules\Promotions\Models\PromotionCode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminPromotionCodeController extends Controller
{
    public function index(ListAdminPromotionCodesAction $listPromotionCodes): View
    {
        return view('admin.promotions.index', [
            'promotions' => $listPromotionCodes->execute(),
        ]);
    }

    public function create(): View
    {
        return view('admin.promotions.form', [
            'promotion' => new PromotionCode,
        ]);
    }

    public function store(StoreAdminPromotionCodeRequest $request, CreatePromotionCodeAction $createPromotionCode): RedirectResponse
    {
        $promotion = $createPromotionCode->execute($request->validated());

        return redirect()
            ->route('admin.promotions.edit', $promotion)
            ->with('success', 'Mã ưu đãi đã được tạo.');
    }

    public function edit(PromotionCode $promotion): View
    {
        return view('admin.promotions.form', compact('promotion'));
    }

    public function update(
        UpdateAdminPromotionCodeRequest $request,
        PromotionCode $promotion,
        UpdatePromotionCodeAction $updatePromotionCode,
    ): RedirectResponse {
        $updatePromotionCode->execute($promotion, $request->validated());

        return redirect()
            ->route('admin.promotions.edit', $promotion)
            ->with('success', 'Mã ưu đãi đã được cập nhật.');
    }

    public function destroy(PromotionCode $promotion, ArchivePromotionCodeAction $archivePromotionCode): RedirectResponse
    {
        $archivePromotionCode->execute($promotion);

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Mã ưu đãi đã được tắt và vẫn được giữ lại cho lịch sử đơn hàng.');
    }
}
