<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Catalog\Actions\ToggleWishlistProductAction;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request, Product $product, ToggleWishlistProductAction $action): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $attached = $action->execute($user, $product);

        if ($request->expectsJson()) {
            return response()->json(['data' => ['active' => $attached, 'message' => $attached ? 'Đã lưu vào danh sách yêu thích.' : 'Đã bỏ khỏi danh sách yêu thích.']]);
        }

        return back()->with('success', $attached ? 'Đã lưu sản phẩm yêu thích.' : 'Đã bỏ sản phẩm khỏi danh sách yêu thích.');
    }
}
