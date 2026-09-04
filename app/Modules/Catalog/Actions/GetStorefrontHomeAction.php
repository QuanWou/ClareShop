<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GetStorefrontHomeAction
{
    public function __construct(private readonly ListVisibleCategoryTreeAction $listCategories) {}

    public function execute(): array
    {
        $categories = $this->listCategories->execute()
            ->where('published_products_count', '>', 0)
            ->take(6)
            ->values();

        $featuredProducts = Product::query()
            ->published()
            ->featured()
            ->withStorefrontSummary()
            ->with(['category', 'categories', 'brand', 'images'])
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        return [
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'collectionImagePool' => $this->collectionImagePool(),
        ];
    }

    /** @return array<int, string> */
    private function collectionImagePool(): array
    {
        $imagesDirectory = public_path('images');

        if (! is_dir($imagesDirectory)) {
            return [];
        }

        return collect(File::allFiles($imagesDirectory))
            ->filter(fn ($file): bool => in_array(strtolower($file->getExtension()), [
                'avif',
                'gif',
                'jpeg',
                'jpg',
                'png',
                'webp',
            ], true))
            // Natural sorting keeps numbered files (for example moi1…moi8)
            // in the same order users see in the images directory.
            ->sort(fn ($first, $second): int => strnatcasecmp(
                strtolower($first->getPathname()),
                strtolower($second->getPathname()),
            ))
            ->map(fn ($file): string => Str::after(
                str_replace('\\', '/', $file->getPathname()),
                str_replace('\\', '/', public_path()).'/',
            ))
            ->filter()
            ->values()
            ->all();
    }
}
