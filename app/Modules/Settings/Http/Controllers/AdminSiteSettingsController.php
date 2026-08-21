<?php

namespace App\Modules\Settings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Settings\Actions\UpdateSiteSettingsAction;
use App\Modules\Settings\Http\Requests\UpdateSiteSettingsRequest;
use App\Modules\Settings\Support\SiteSettingsRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminSiteSettingsController extends Controller
{
    public function edit(SiteSettingsRegistry $settings): View
    {
        return view('admin.settings.edit', ['settings' => $settings]);
    }

    public function update(UpdateSiteSettingsRequest $request, UpdateSiteSettingsAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('success', 'Đã lưu cấu hình cửa hàng.');
    }
}
