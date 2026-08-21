<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;

class ShowPublishedProductAction
{
    public function execute(Product $product): array
    {
        $product = Product::query()
            ->published()
            ->withStorefrontSummary()
            ->with([
                'category',
                'categories',
                'brand',
                'activeVariants.images',
                'images',
                'attributeValues.attribute',
                'reviews' => fn ($query) => $query
                    ->approved()
                    ->with(['user', 'images'])
                    ->latest('approved_at'),
            ])
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->approved()])
            ->withAvg(['reviews as approved_reviews_average' => fn ($query) => $query->approved()], 'rating')
            ->when(auth()->check(), fn ($query) => $query->withExists([
                'wishlistedBy as is_wishlisted' => fn ($query) => $query->whereKey(auth()->id()),
            ]))
            ->whereKey($product->getKey())
            ->firstOrFail();
        $product->setAttribute('sold_count', (int) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('product_variants.product_id', $product->getKey())
            ->where('orders.status', 'completed')
            ->sum('order_items.quantity'));

        $relatedProducts = Product::query()
            ->published()
            ->withStorefrontSummary()
            ->with(['category', 'brand', 'images'])
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->approved()])
            ->withAvg(['reviews as approved_reviews_average' => fn ($query) => $query->approved()], 'rating')
            ->when(auth()->check(), fn ($query) => $query->withExists([
                'wishlistedBy as is_wishlisted' => fn ($query) => $query->whereKey(auth()->id()),
            ]))
            ->where(function ($query) use ($product): void {
                $categoryIds = $product->categories->pluck('id');
                $query
                    ->whereIn('category_id', $categoryIds)
                    ->orWhereHas('categories', fn ($query) => $query->whereKey($categoryIds));
            })
            ->whereKeyNot($product->getKey())
            ->orderByDesc('is_featured')
            ->limit(4)
            ->get();

        $reviewDistribution = $product->reviews
            ->countBy('rating')
            ->mapWithKeys(fn ($count, $rating) => [(int) $rating => $count]);
        $viewerReview = null;
        $canReview = false;

        if (auth()->check()) {
            $viewerReview = $product->reviews()
                ->where('user_id', auth()->id())
                ->first();
            $canReview = $viewerReview === null && Order::query()
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->whereHas('items.variant', fn ($query) => $query->where('product_id', $product->getKey()))
                ->exists();
        }

        return compact('product', 'relatedProducts', 'reviewDistribution', 'viewerReview', 'canReview');
    }
}
