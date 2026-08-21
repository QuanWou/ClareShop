<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Http\Requests\UpdateProductReviewStatusRequest;
use App\Modules\Catalog\Actions\ModerateProductReviewAction;
use App\Modules\Catalog\Models\ProductReview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminProductReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->string('status')->toString(), ['pending', 'approved', 'hidden'], true)
            ? $request->string('status')->toString()
            : null;

        return view('admin.reviews.index', [
            'reviews' => ProductReview::query()
                ->with(['product', 'user', 'order', 'images'])
                ->when($status, fn ($query) => $query->where('status', $status))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'statusFilter' => $status,
        ]);
    }

    public function update(UpdateProductReviewStatusRequest $request, ProductReview $review, ModerateProductReviewAction $action): RedirectResponse
    {
        $action->execute($review, $request->validated());

        return back()->with('status', 'Đã cập nhật trạng thái đánh giá.');
    }
}
