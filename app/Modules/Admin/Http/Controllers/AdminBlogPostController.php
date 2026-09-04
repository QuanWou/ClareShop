<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Actions\ListAdminBlogPostsAction;
use App\Modules\Blog\Actions\ArchiveBlogPostAction;
use App\Modules\Blog\Actions\CreateBlogPostAction;
use App\Modules\Blog\Actions\RestoreBlogPostAction;
use App\Modules\Blog\Actions\UpdateBlogPostAction;
use App\Modules\Blog\Http\Requests\SaveBlogPostRequest;
use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Blog\Models\BlogPost;
use App\Modules\Blog\Models\BlogTag;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminBlogPostController extends Controller
{
    public function index(ListAdminBlogPostsAction $action): View
    {
        return view('admin.blog.posts.index', $action->execute());
    }

    public function create(): View
    {
        return view('admin.blog.posts.create', $this->formData());
    }

    public function store(SaveBlogPostRequest $request, CreateBlogPostAction $action): RedirectResponse
    {
        /** @var User $author */
        $author = $request->user();
        $post = $action->execute($author, $request->validated());

        return redirect()->route('admin.blog.posts.edit', $post)->with('success', 'Đã tạo bài viết.');
    }

    public function edit(BlogPost $post): View
    {
        $post->load(['tags', 'products']);

        return view('admin.blog.posts.edit', [...$this->formData(), 'post' => $post]);
    }

    public function update(SaveBlogPostRequest $request, BlogPost $post, UpdateBlogPostAction $action): RedirectResponse
    {
        $action->execute($post, $request->validated());

        return back()->with('success', 'Đã cập nhật bài viết.');
    }

    public function destroy(BlogPost $post, ArchiveBlogPostAction $action): RedirectResponse
    {
        $action->execute($post);

        return redirect()->route('admin.blog.posts.index')->with('success', 'Đã lưu trữ bài viết.');
    }

    public function restore(BlogPost $post, RestoreBlogPostAction $action): RedirectResponse
    {
        $action->execute($post);

        return redirect()->route('admin.blog.posts.edit', $post)->with('success', 'Đã khôi phục bài viết. Hãy rà soát nội dung trước khi xuất bản lại.');
    }

    private function formData(): array
    {
        return [
            'categories' => BlogCategory::query()->orderBy('sort_order')->get(),
            'tags' => BlogTag::query()->orderBy('name')->get(),
            'products' => Product::query()->published()->orderBy('name')->get(),
        ];
    }
}
