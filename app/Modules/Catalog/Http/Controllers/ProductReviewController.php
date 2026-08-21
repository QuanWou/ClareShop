<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Catalog\Actions\CreateProductReviewAction;
use App\Modules\Catalog\Http\Requests\StoreProductReviewRequest;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\RedirectResponse;

class ProductReviewController extends Controller
{
    public function store(StoreProductReviewRequest $request, Product $product, CreateProductReviewAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->execute($product, $user, $request->validated());

        return redirect()
            ->route('catalog.products.show', $product)
            ->with('status', 'Cảm ơn bạn. Đánh giá đang chờ Clare duyệt trước khi hiển thị.');
    }
}
