<?php

namespace App\Modules\Blog\Actions;

use App\Models\User;
use App\Modules\Blog\Models\BlogPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateBlogPostAction
{
    /** @param array<string, mixed> $validated */
    public function execute(User $author, array $validated): BlogPost
    {
        return DB::transaction(function () use ($author, $validated): BlogPost {
            $post = BlogPost::query()->create([
                ...$this->attributes($validated),
                'author_id' => $author->getKey(),
                'featured_image_path' => isset($validated['featured_image']) ? $validated['featured_image']->store('blog', 'public') : null,
            ]);
            $post->tags()->sync($validated['tag_ids'] ?? []);
            $post->products()->sync($validated['product_ids'] ?? []);

            return $post;
        });
    }

    /** @param array<string, mixed> $validated */
    private function attributes(array $validated): array
    {
        return [
            'blog_category_id' => $validated['blog_category_id'] ?? null,
            'title' => $validated['title'],
            'slug' => ($validated['slug'] ?? null) ?: Str::slug($validated['title']).'-'.Str::lower(Str::random(5)),
            'excerpt' => $validated['excerpt'] ?? null,
            'content' => $validated['content'],
            'featured_image_alt' => $validated['featured_image_alt'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'published_at' => $validated['status'] === 'published' ? ($validated['published_at'] ?? now()) : null,
            'seo_title' => $validated['seo_title'] ?? null,
            'seo_description' => $validated['seo_description'] ?? null,
        ];
    }
}
