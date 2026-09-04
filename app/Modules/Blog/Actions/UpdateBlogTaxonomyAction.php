<?php

namespace App\Modules\Blog\Actions;

use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Blog\Models\BlogTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UpdateBlogTaxonomyAction
{
    /** @param array<string, mixed> $validated */
    public function execute(BlogCategory|BlogTag $taxonomy, array $validated): Model
    {
        $attributes = [
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($taxonomy, $validated['name']),
        ];

        if ($taxonomy instanceof BlogCategory) {
            $attributes += [
                'description' => $validated['description'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ];
        }

        $taxonomy->update($attributes);

        return $taxonomy->refresh();
    }

    private function uniqueSlug(BlogCategory|BlogTag $taxonomy, string $name): string
    {
        $base = Str::slug($name) ?: 'phan-loai';
        $slug = $base;
        $suffix = 2;

        while ($taxonomy->newQuery()->where('slug', $slug)->whereKeyNot($taxonomy->getKey())->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
