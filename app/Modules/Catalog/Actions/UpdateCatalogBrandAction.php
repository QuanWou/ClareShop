<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class UpdateCatalogBrandAction
{
    public function __construct(private readonly ResolveUniqueCatalogSlugAction $resolveSlug) {}

    public function execute(Brand $brand, array $validated): Brand
    {
        /** @var UploadedFile|null $logo */
        $logo = $validated['brand_logo'] ?? null;
        $data = Arr::except($validated, ['brand_logo']);
        $data['slug'] = $this->resolveSlug->execute(($data['slug'] ?? null) ?: $data['name'], Brand::class, $brand->getKey());
        $previousLogo = $brand->logo_path;

        if ($logo !== null) {
            $data['logo_path'] = 'storage/'.$logo->store('catalog/brands', 'public');
        }

        $brand->update($data);

        if ($logo !== null && $previousLogo && str_starts_with($previousLogo, 'storage/')) {
            Storage::disk('public')->delete(substr($previousLogo, strlen('storage/')));
        }

        return $brand->fresh();
    }
}
