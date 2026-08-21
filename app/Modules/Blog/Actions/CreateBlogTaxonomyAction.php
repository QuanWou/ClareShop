<?php

namespace App\Modules\Blog\Actions;

use App\Modules\Blog\Models\BlogCategory;
use App\Modules\Blog\Models\BlogTag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateBlogTaxonomyAction
{
    /** @param array{type: string, name: string, description?: string|null} $validated */
    public function execute(array $validated): Model
    {
        $slug = Str::slug($validated['name']);

        if ($validated['type'] === 'tag') {
            return BlogTag::query()->firstOrCreate(['slug' => $slug], ['name' => $validated['name']]);
        }

        return BlogCategory::query()->firstOrCreate(['slug' => $slug], [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);
    }
}
