<?php

namespace App\Modules\Blog\Actions;

use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Blog\Models\BlogPost;

class ListPublishedBlogPostsAction
{
    public function execute(?string $categorySlug = null): array
    {
        $category = $categorySlug ? BlogCategory::query()->where('slug', $categorySlug)->where('is_active', true)->firstOrFail() : null;

        return [
            'posts' => BlogPost::query()
                ->published()
                ->with(['category', 'author', 'tags'])
                ->when($category, fn ($query) => $query->whereBelongsTo($category, 'category'))
                ->orderByDesc('is_featured')
                ->latest('published_at')
                ->paginate(9)
                ->withQueryString(),
            'categories' => BlogCategory::query()->where('is_active', true)->withCount(['posts' => fn ($query) => $query->published()])->orderBy('sort_order')->get(),
            'selectedCategory' => $category,
        ];
    }
}
