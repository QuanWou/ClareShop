<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class CreateCatalogBrandAction
{
    public function __construct(private readonly ResolveUniqueCatalogSlugAction $resolveSlug) {}

    public function execute(array $validated): Brand
    {
        /** @var UploadedFile|null $logo */
        $logo = $validated['brand_logo'] ?? null;
        $data = Arr::except($validated, ['brand_logo']);
        $data['slug'] = $this->resolveSlug->execute(($data['slug'] ?? null) ?: $data['name'], Brand::class);

        if ($logo !== null) {
            $data['logo_path'] = 'storage/'.$logo->store('catalog/brands', 'public');
        }

        return Brand::query()->create($data);
    }
}
