<?php

namespace App\Modules\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Actions\AddItemToCartAction;
use App\Modules\Cart\Actions\ClearCartAction;
use App\Modules\Cart\Actions\RemoveCartItemAction;
use App\Modules\Cart\Actions\ResolveCartAction;
use App\Modules\Cart\Actions\SelectCartItemsForCheckoutAction;
use App\Modules\Cart\Actions\ShowCartAction;
use App\Modules\Cart\Actions\UpdateCartItemAction;
use App\Modules\Cart\Data\CartResolution;
use App\Modules\Cart\Http\Requests\AddCartItemRequest;
use App\Modules\Cart\Http\Requests\SelectCartItemsRequest;
use App\Modules\Cart\Http\Requests\UpdateCartItemRequest;
use App\Modules\Cart\Support\CartCookie;
use App\Modules\Promotions\Actions\GetCartVoucherPreviewAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CartController extends Controller
{
    public function show(
        Request $request,
        ResolveCartAction $resolveCart,
        ShowCartAction $showCart,
        GetCartVoucherPreviewAction $getVoucherPreview,
    ): Response {
        $resolution = $this->resolve($request, $resolveCart, create: false);
        $summary = $showCart->execute($resolution->cart);

        return response()->view('cart.show', [
            ...$summary,
            'cartVoucher' => $getVoucherPreview->execute(
                $request->user(),
                $request->session()->get('checkout.discount_code'),
                $summary['selectedSubtotal'],
            ),
        ]);
    }

    public function checkout(
        SelectCartItemsRequest $request,
        ResolveCartAction $resolveCart,
        SelectCartItemsForCheckoutAction $selectItems,
    ): RedirectResponse {
        $resolution = $this->resolve($request, $resolveCart, create: false);
        abort_if($resolution->cart === null, 404);

        $selectItems->execute(
            $resolution->cart,
            $request->validated('cart_item_ids'),
        );

        return redirect()->route('checkout.show');
    }

    public function store(
        AddCartItemRequest $request,
        AddItemToCartAction $addItemToCart,
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        $resolution = $addItemToCart->execute(
            userId: $this->userId($request),
            guestToken: $request->cookie(config('commerce.cart.cookie')),
            variantId: (int) $validated['product_variant_id'],
            quantity: (int) $validated['quantity'],
        );

        if ($request->expectsJson()) {
            $response = response()->json([
                'data' => [
                    'message' => 'Đã thêm sản phẩm vào giỏ hàng.',
                    'cart_item_count' => (int) $resolution->cart->items()->sum('quantity'),
                    'cart_url' => route('cart.show'),
                ],
            ]);

            return $this->attachGuestCookie($response, $resolution, $request);
        }

        $response = redirect()
            ->route('cart.show')
            ->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');

        return $this->attachGuestCookie($response, $resolution, $request);
    }

    public function update(
        UpdateCartItemRequest $request,
        int $cartItem,
        ResolveCartAction $resolveCart,
        UpdateCartItemAction $updateCartItem,
    ): RedirectResponse {
        $resolution = $this->resolve($request, $resolveCart, create: false);
        abort_if($resolution->cart === null, 404);

        $updateCartItem->execute(
            cart: $resolution->cart,
            cartItemId: $cartItem,
            quantity: (int) $request->validated('quantity'),
        );

        return redirect()
            ->route('cart.show')
            ->with('success', 'Đã cập nhật số lượng.');
    }

    public function destroy(
        Request $request,
        int $cartItem,
        ResolveCartAction $resolveCart,
        RemoveCartItemAction $removeCartItem,
    ): RedirectResponse {
        $resolution = $this->resolve($request, $resolveCart, create: false);
        abort_if($resolution->cart === null, 404);

        $removeCartItem->execute($resolution->cart, $cartItem);

        return redirect()
            ->route('cart.show')
            ->with('success', 'Đã bỏ sản phẩm khỏi giỏ hàng.');
    }

    public function clear(
        Request $request,
        ResolveCartAction $resolveCart,
        ClearCartAction $clearCart,
    ): RedirectResponse {
        $resolution = $this->resolve($request, $resolveCart, create: false);

        if ($resolution->cart !== null) {
            $clearCart->execute($resolution->cart);
        }

        $request->session()->forget('checkout.discount_code');

        return redirect()
            ->route('cart.show')
            ->with('success', 'Giỏ hàng đã được làm trống.');
    }

    private function resolve(Request $request, ResolveCartAction $action, bool $create): CartResolution
    {
        return $action->execute(
            userId: $this->userId($request),
            guestToken: $request->cookie(config('commerce.cart.cookie')),
            create: $create,
        );
    }

    private function userId(Request $request): ?int
    {
        $identifier = $request->user()?->getAuthIdentifier();

        return $identifier === null ? null : (int) $identifier;
    }

    private function attachGuestCookie(
        RedirectResponse|JsonResponse $response,
        CartResolution $resolution,
        Request $request,
    ): RedirectResponse|JsonResponse {
        if ($resolution->guestToken !== null) {
            $response->withCookie(CartCookie::make($resolution->guestToken, $request->isSecure()));
        }

        return $response;
    }
}
