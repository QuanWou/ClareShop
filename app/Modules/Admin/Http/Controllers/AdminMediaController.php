<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Actions\ListAdminMediaAssetsAction;
use App\Modules\Media\Actions\DeleteMediaAssetAction;
use App\Modules\Media\Actions\UpdateMediaAssetAction;
use App\Modules\Media\Actions\UploadMediaAssetsAction;
use App\Modules\Media\Http\Requests\StoreMediaAssetRequest;
use App\Modules\Media\Http\Requests\UpdateMediaAssetRequest;
use App\Modules\Media\Models\MediaAsset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminMediaController extends Controller
{
    public function index(ListAdminMediaAssetsAction $action): View
    {
        return view('admin.media.index', $action->execute());
    }

    public function store(StoreMediaAssetRequest $request, UploadMediaAssetsAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->execute($user, $request->file('files'), $request->validated('alt_text'));

        return back()->with('success', 'Đã tải ảnh vào thư viện.');
    }

    public function destroy(MediaAsset $asset, DeleteMediaAssetAction $action): RedirectResponse
    {
        $action->execute($asset);

        return back()->with('success', 'Đã xóa tệp khỏi thư viện.');
    }

    public function update(MediaAsset $asset, UpdateMediaAssetRequest $request, UpdateMediaAssetAction $action): RedirectResponse
    {
        $action->execute($asset, $request->validated());

        return back()->with('success', 'Đã cập nhật mô tả ảnh.');
    }
}
