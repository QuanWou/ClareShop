<?php

namespace App\Modules\Blog\Actions;

use App\Modules\Blog\Models\BlogPost;

class ShowPublishedBlogPostAction
{
    public function execute(BlogPost $post): array
    {
        $post = BlogPost::query()->published()->with(['category', 'author', 'tags', 'products' => fn ($query) => $query->published()->withStorefrontSummary()->with(['category', 'images'])])->whereKey($post->getKey())->firstOrFail();

        $relatedPosts = BlogPost::query()
            ->published()
            ->with('category')
            ->whereKeyNot($post->getKey())
            ->when($post->blog_category_id, fn ($query) => $query->where('blog_category_id', $post->blog_category_id))
            ->latest('published_at')
            ->limit(3)
            ->get();

        return compact('post', 'relatedPosts');
    }
}
