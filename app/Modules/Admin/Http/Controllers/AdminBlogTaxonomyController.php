<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Blog\Actions\CreateBlogTaxonomyAction;
use App\Modules\Blog\Http\Requests\SaveBlogTaxonomyRequest;
use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Blog\Models\BlogTag;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminBlogTaxonomyController extends Controller
{
    public function index(): View
    {
        return view('admin.blog.taxonomy', [
            'categories' => BlogCategory::query()->withCount('posts')->orderBy('sort_order')->get(),
            'tags' => BlogTag::query()->withCount('posts')->orderBy('name')->get(),
        ]);
    }

    public function store(SaveBlogTaxonomyRequest $request, CreateBlogTaxonomyAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('success', 'Đã thêm phân loại nội dung.');
    }
}
