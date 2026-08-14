<?php

namespace App\Modules\Catalog\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ResolveUniqueCatalogSlugAction
{
    /** @param class-string<Model> $modelClass */
    public function execute(string $source, string $modelClass, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($source) ?: 'catalog-item';
        $slug = $baseSlug;
        $suffix = 2;

        while ($modelClass::query()
            ->withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
