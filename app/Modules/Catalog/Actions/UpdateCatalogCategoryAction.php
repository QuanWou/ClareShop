<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class UpdateCatalogCategoryAction
{
    public function __construct(private readonly ResolveUniqueCatalogSlugAction $resolveSlug) {}

    public function execute(Category $category, array $validated): Category
    {
        /** @var UploadedFile|null $image */
        $image = $validated['category_image'] ?? null;
        $data = Arr::except($validated, ['category_image']);
        $data['slug'] = $this->resolveSlug->execute(($data['slug'] ?? null) ?: $data['name'], Category::class, $category->getKey());
        $previousImagePath = $category->image_path;

        if ($image !== null) {
            $data['image_path'] = 'storage/'.$image->store('catalog/categories', 'public');
        }

        $category->update($data);

        if ($image !== null && $previousImagePath && str_starts_with($previousImagePath, 'storage/')) {
            Storage::disk('public')->delete(substr($previousImagePath, strlen('storage/')));
        }

        return $category->fresh();
    }
}
