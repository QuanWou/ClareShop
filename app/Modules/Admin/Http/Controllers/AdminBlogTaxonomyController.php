<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Blog\Actions\CreateBlogTaxonomyAction;
use App\Modules\Blog\Actions\DeleteBlogTaxonomyAction;
use App\Modules\Blog\Actions\UpdateBlogTaxonomyAction;
use App\Modules\Blog\Http\Requests\SaveBlogTaxonomyRequest;
use App\Modules\Blog\Http\Requests\UpdateBlogTaxonomyRequest;
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

    public function updateCategory(
        UpdateBlogTaxonomyRequest $request,
        BlogCategory $category,
        UpdateBlogTaxonomyAction $action,
    ): RedirectResponse {
        $action->execute($category, $request->validated());

        return back()->with('success', 'Danh mục bài viết đã được cập nhật.');
    }

    public function destroyCategory(BlogCategory $category, DeleteBlogTaxonomyAction $action): RedirectResponse
    {
        $action->execute($category);

        return back()->with('success', 'Đã xóa danh mục. Các bài viết cũ được giữ lại và chuyển sang chưa phân loại.');
    }

    public function updateTag(
        UpdateBlogTaxonomyRequest $request,
        BlogTag $tag,
        UpdateBlogTaxonomyAction $action,
    ): RedirectResponse {
        $action->execute($tag, $request->validated());

        return back()->with('success', 'Thẻ bài viết đã được cập nhật.');
    }

    public function destroyTag(BlogTag $tag, DeleteBlogTaxonomyAction $action): RedirectResponse
    {
        $action->execute($tag);

        return back()->with('success', 'Đã xóa thẻ. Các bài viết vẫn được giữ nguyên.');
    }
}
