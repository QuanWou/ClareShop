<?php

namespace App\Modules\Blog\Actions;

use App\Modules\Blog\Models\BlogPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateBlogPostAction
{
    /** @param array<string, mixed> $validated */
    public function execute(BlogPost $post, array $validated): BlogPost
    {
        return DB::transaction(function () use ($post, $validated): BlogPost {
            $imagePath = $post->featured_image_path;

            if (($validated['remove_featured_image'] ?? false) || isset($validated['featured_image'])) {
                if ($imagePath) {
                    Storage::disk('public')->delete($imagePath);
                }
                $imagePath = isset($validated['featured_image']) ? $validated['featured_image']->store('blog', 'public') : null;
            }

            $post->update([
                'blog_category_id' => $validated['blog_category_id'] ?? null,
                'title' => $validated['title'],
                'slug' => ($validated['slug'] ?? null) ?: (Str::slug($validated['title']).'-'.$post->getKey()),
                'excerpt' => $validated['excerpt'] ?? null,
                'content' => $validated['content'],
                'featured_image_path' => $imagePath,
                'featured_image_alt' => $validated['featured_image_alt'] ?? null,
                'status' => $validated['status'],
                'is_featured' => (bool) ($validated['is_featured'] ?? false),
                'published_at' => $validated['status'] === 'published' ? ($validated['published_at'] ?? $post->published_at ?? now()) : null,
                'seo_title' => $validated['seo_title'] ?? null,
                'seo_description' => $validated['seo_description'] ?? null,
            ]);
            $post->tags()->sync($validated['tag_ids'] ?? []);
            $post->products()->sync($validated['product_ids'] ?? []);

            return $post->refresh();
        });
    }
}
