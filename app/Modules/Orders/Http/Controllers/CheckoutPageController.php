<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cart\Actions\ResolveCartAction;
use App\Modules\Cart\Actions\ShowCartAction;
use App\Modules\Cart\Data\CartResolution;
use App\Modules\Customers\Actions\GetDefaultUserAddressAction;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\Http\Requests\CreateCheckoutOrderRequest;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Support\PaymentMethodCatalog;
use App\Modules\Orders\Support\ShippingOptionCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CheckoutPageController extends Controller
{
    public function show(
        Request $request,
        ResolveCartAction $resolveCart,
        ShowCartAction $showCart,
        GetDefaultUserAddressAction $getDefaultAddress,
    ): View|RedirectResponse {
        $resolution = $this->resolve($request, $resolveCart);
        $summary = $showCart->execute($resolution->cart);

        if ($summary['cartLines']->isEmpty()) {
            return redirect()
                ->route('cart.show')
                ->withErrors(['cart' => 'Giỏ hàng đang trống. Hãy chọn một sản phẩm trước khi checkout.']);
        }

        return view('orders.checkout', [
            ...$summary,
            'customer' => $this->customer($request),
            'defaultAddress' => $getDefaultAddress->execute($this->customer($request)),
            'shippingOptions' => ShippingOptionCatalog::forCheckout(),
            'defaultShippingOption' => ShippingOptionCatalog::defaultCode(),
            'paymentMethods' => PaymentMethodCatalog::all(),
        ]);
    }

    public function store(
        CreateCheckoutOrderRequest $request,
        ResolveCartAction $resolveCart,
        CreateOrderAction $createOrder,
    ): RedirectResponse {
        $resolution = $this->resolve($request, $resolveCart);

        if ($resolution->cart === null) {
            return redirect()
                ->route('cart.show')
                ->withErrors(['cart' => 'Giỏ hàng đang trống. Hãy chọn một sản phẩm trước khi checkout.']);
        }

        $result = $createOrder->execute(
            cart: $resolution->cart,
            customer: $this->customer($request),
            validated: $request->validated(),
        );

        return redirect()->to(URL::temporarySignedRoute(
            'checkout.complete',
            now()->addDays(7),
            ['orderNumber' => $result->order->number],
        ));
    }

    public function complete(Request $request, string $orderNumber): View
    {
        $order = Order::query()
            ->where('number', $orderNumber)
            ->with(['items', 'payments', 'discount', 'statusHistories'])
            ->firstOrFail();

        abort_unless((int) $order->user_id === $this->userId($request), 404);

        return view('orders.complete', [
            'order' => $order,
            'payment' => $order->payments->sortByDesc('id')->first(),
            'paymentMethod' => PaymentMethodCatalog::get($order->payment_method),
        ]);
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
