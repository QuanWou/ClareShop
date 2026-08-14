<?php

namespace App\Modules\Content\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Content\Actions\UpdateSiteContentAction;
use App\Modules\Content\Http\Requests\UpdateSiteContentRequest;
use App\Modules\Content\Support\SiteContentRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminSiteContentController extends Controller
{
    public function edit(SiteContentRegistry $registry): View
    {
        return view('admin.content.edit', [
            'contentGroups' => $registry->groupsForAdmin(),
        ]);
    }

    public function update(UpdateSiteContentRequest $request, UpdateSiteContentAction $action): RedirectResponse
    {
        $validated = $request->validated();

        $action->execute(
            $validated['content'],
            $validated['images'] ?? [],
            $request->user(),
        );

        return redirect()
            ->route('admin.content.edit')
            ->with('success', 'Nội dung website đã được cập nhật.');
    }
}
