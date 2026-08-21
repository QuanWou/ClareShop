<?php

namespace App\Modules\Cart\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cart\Actions\AddItemToCartAction;
use App\Modules\Cart\Http\Requests\AddCartItemRequest;
use App\Modules\Cart\Support\CartCookie;
use Illuminate\Http\RedirectResponse;

class BuyNowController extends Controller
{
    public function __invoke(AddCartItemRequest $request, AddItemToCartAction $action): RedirectResponse
    {
        $validated = $request->validated();
        $identifier = $request->user()?->getAuthIdentifier();
        $result = $action->execute(
            userId: $identifier === null ? null : (int) $identifier,
            guestToken: $request->cookie(config('commerce.cart.cookie')),
            variantId: (int) $validated['product_variant_id'],
            quantity: (int) $validated['quantity'],
        );
        $response = redirect()->route('checkout.show');

        if ($result->guestToken !== null) {
            $response->withCookie(CartCookie::make($result->guestToken, $request->isSecure()));
        }

        return $response;
    }
}
