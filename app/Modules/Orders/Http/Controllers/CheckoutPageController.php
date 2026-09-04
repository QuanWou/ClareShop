<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Cart\Actions\ResolveCartAction;
use App\Modules\Cart\Actions\ShowCartAction;
use App\Modules\Cart\Data\CartResolution;
use App\Modules\Customers\Actions\GetDefaultUserAddressAction;
use App\Modules\Orders\Actions\CreateOrderAction;
use App\Modules\Orders\Actions\QuoteCheckoutAction;
use App\Modules\Orders\Actions\ResolvePayOsPaymentAction;
use App\Modules\Orders\Data\ShippingAddressData;
use App\Modules\Orders\Http\Requests\CreateCheckoutOrderRequest;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Support\PaymentMethodCatalog;
use App\Modules\Orders\Support\ShippingOptionCatalog;
use App\Modules\Promotions\Actions\ListCheckoutVoucherOptionsAction;
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
        ListCheckoutVoucherOptionsAction $listVoucherOptions,
        QuoteCheckoutAction $quoteCheckout,
    ): View|RedirectResponse {
        $resolution = $this->resolve($request, $resolveCart);
        $summary = $showCart->execute($resolution->cart, selectedOnly: true);

        if ($summary['cartLines']->isEmpty()) {
            return redirect()
                ->route('cart.show')
                ->withErrors(['cart' => 'Bạn chưa chọn sản phẩm nào để thanh toán.']);
        }

        $savedAddresses = $this->customer($request)->addresses()->orderByDesc('is_default')->latest()->get();
        $defaultAddress = $getDefaultAddress->execute($this->customer($request)) ?? $savedAddresses->first();
        $initialQuote = null;
        $selectedShippingOption = $request->old('shipping_option', ShippingOptionCatalog::defaultCode());

        if (! in_array($selectedShippingOption, ShippingOptionCatalog::codes(), true)) {
            $selectedShippingOption = ShippingOptionCatalog::defaultCode();
        }

        if ($defaultAddress !== null && collect([
            $defaultAddress->recipient_name,
            $defaultAddress->phone,
            $defaultAddress->address_line_1,
            $defaultAddress->ward,
            $defaultAddress->district,
            $defaultAddress->city,
            $defaultAddress->country_code,
        ])->every(fn ($value) => filled($value))) {
            $initialQuote = $quoteCheckout->execute(
                cart: $resolution->cart,
                address: new ShippingAddressData(
                    recipientName: $defaultAddress->recipient_name,
                    phone: $defaultAddress->phone,
                    addressLine1: $defaultAddress->address_line_1,
                    addressLine2: $defaultAddress->address_line_2,
                    ward: $defaultAddress->ward,
                    district: $defaultAddress->district,
                    city: $defaultAddress->city,
                    postalCode: $defaultAddress->postal_code,
                    countryCode: $defaultAddress->country_code,
                ),
                discountCode: $request->session()->get('checkout.discount_code'),
                shippingOption: $selectedShippingOption,
                customer: $this->customer($request),
            );
        }

        return view('orders.checkout', [
            ...$summary,
            'customer' => $this->customer($request),
            'defaultAddress' => $defaultAddress,
            'savedAddresses' => $savedAddresses,
            'shippingOptions' => ShippingOptionCatalog::forCheckout(),
            'defaultShippingOption' => ShippingOptionCatalog::defaultCode(),
            'paymentMethods' => PaymentMethodCatalog::all(),
            'selectedDiscountCode' => $request->session()->get('checkout.discount_code'),
            'voucherOptions' => $listVoucherOptions->execute($this->customer($request), $summary['subtotal']),
            'initialQuote' => $initialQuote,
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

        $request->session()->forget('checkout.discount_code');

        if (in_array($result->payment->provider, ['paypal', 'momo'], true) && filled($result->payment->approval_url)) {
            return redirect()->away((string) $result->payment->approval_url);
        }

        return redirect()->to(URL::temporarySignedRoute(
            'checkout.complete',
            now()->addDays(7),
            ['orderNumber' => $result->order->number],
        ));
    }

    public function complete(
        Request $request,
        string $orderNumber,
        ResolvePayOsPaymentAction $resolvePayOsPayment,
    ): View {
        $order = Order::query()
            ->where('number', $orderNumber)
            ->with([
                'items.variant.images',
                'items.variant.product.images',
                'payments',
                'discount',
                'statusHistories',
            ])
            ->firstOrFail();

        abort_unless((int) $order->user_id === $this->userId($request), 404);

        $payment = $order->payments->sortByDesc('id')->first();

        return view('orders.complete', [
            'order' => $order,
            'payment' => $payment,
            'paymentMethod' => PaymentMethodCatalog::get($order->payment_method),
            'paymentMethods' => PaymentMethodCatalog::all(),
            'payOs' => $resolvePayOsPayment->execute($order, $payment),
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
