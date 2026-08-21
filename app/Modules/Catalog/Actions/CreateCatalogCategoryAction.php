<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class CreateCatalogCategoryAction
{
    public function __construct(
        private readonly ResolveUniqueCatalogSlugAction $resolveSlug,
        private readonly EnsureValidCategoryParentAction $ensureValidParent,
    ) {}

    public function execute(array $validated): Category
    {
        $this->ensureValidParent->execute(null, $validated['parent_id'] ?? null);
        /** @var UploadedFile|null $image */
        $image = $validated['category_image'] ?? null;
        $data = Arr::except($validated, ['category_image']);
        $data['slug'] = $this->resolveSlug->execute(($data['slug'] ?? null) ?: $data['name'], Category::class);

        if ($image !== null) {
            $data['image_path'] = 'storage/'.$image->store('catalog/categories', 'public');
        }

        return Category::query()->create($data);
    }
}
