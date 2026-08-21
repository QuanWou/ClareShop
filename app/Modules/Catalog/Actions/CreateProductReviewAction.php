<?php

namespace App\Modules\Catalog\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductReview;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateProductReviewAction
{
    /** @param array<string, mixed> $validated */
    public function execute(Product $product, User $user, array $validated): ProductReview
    {
        if ($product->reviews()->where('user_id', $user->getKey())->exists()) {
            throw ValidationException::withMessages(['review' => 'Bạn đã đánh giá sản phẩm này.']);
        }

        $order = Order::query()
            ->where('user_id', $user->getKey())
            ->where('status', 'completed')
            ->whereHas('items.variant', fn ($query) => $query->where('product_id', $product->getKey()))
            ->latest('delivered_at')
            ->first();

        if ($order === null) {
            throw ValidationException::withMessages(['review' => 'Chỉ khách đã nhận sản phẩm mới có thể gửi đánh giá.']);
        }

        return DB::transaction(function () use ($product, $user, $order, $validated): ProductReview {
            $review = $product->reviews()->create([
                'user_id' => $user->getKey(),
                'order_id' => $order->getKey(),
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'comment' => $validated['comment'],
                'status' => 'pending',
                'is_verified_purchase' => true,
            ]);

            foreach (($validated['images'] ?? []) as $index => $image) {
                if ($image instanceof UploadedFile) {
                    $review->images()->create([
                        'path' => $image->store('reviews', 'public'),
                        'sort_order' => $index,
                    ]);
                }
            }

            return $review;
        });
    }
}
