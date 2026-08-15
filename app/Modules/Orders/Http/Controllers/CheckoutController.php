<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cart\Actions\ResolveCartAction;
use App\Modules\Cart\Data\CartResolution;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\Actions\QuoteCheckoutAction;
use App\Modules\Orders\Data\ShippingAddressData;
use App\Modules\Orders\Http\Requests\CreateCheckoutOrderRequest;
use App\Modules\Orders\Http\Requests\QuoteCheckoutRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function quote(
        QuoteCheckoutRequest $request,
        ResolveCartAction $resolveCart,
        QuoteCheckoutAction $quoteCheckout,
    ): JsonResponse {
        $resolution = $this->resolve($request, $resolveCart);
        $quote = $quoteCheckout->execute(
            cart: $resolution->cart,
            address: ShippingAddressData::fromValidated($request->validated()),
            discountCode: $request->validated('discount_code'),
            shippingOption: $request->validated('shipping_option'),
        );

        return response()->json(['data' => $quote->toArray()]);
    }

    public function store(
        CreateCheckoutOrderRequest $request,
        ResolveCartAction $resolveCart,
        CreateOrderAction $createOrder,
    ): JsonResponse {
        $resolution = $this->resolve($request, $resolveCart);
        abort_if($resolution->cart === null, 422, 'Giỏ hàng đang trống.');

        $result = $createOrder->execute(
            cart: $resolution->cart,
            customer: $this->customer($request),
            validated: $request->validated(),
        );

        return response()->json(['data' => $result->toArray()], 201);
    }

    private function resolve(Request $request, ResolveCartAction $action): CartResolution
    {
        return $action->execute(
            userId: $this->userId($request),
            guestToken: $request->cookie(config('commerce.cart.cookie')),
            create: false,
        );
    }

    private function userId(Request $request): int
    {
        return (int) $this->customer($request)->getAuthIdentifier();
    }

    private function customer(Request $request): User
    {
        /** @var User $customer */
        $customer = $request->user();

        return $customer;
    }
}
