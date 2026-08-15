<?php

namespace App\Modules\Orders\Actions;

use App\Models\User;
use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Orders\Data\CreatedOrderData;
use App\Modules\Orders\Data\ShippingAddressData;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderStatusHistory;
use App\Modules\Orders\Models\PaymentStatusHistory;
use App\Modules\Orders\Support\PaymentMethodCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrderAction
{
    public function __construct(
        private readonly CalculateCheckoutTotalsAction $calculateCheckoutTotals,
        private readonly BuildVietQrPaymentAction $buildVietQrPayment,
        private readonly CalculateEstimatedDeliveryAtAction $calculateEstimatedDeliveryAt,
    ) {}

    public function execute(Cart $cart, User $customer, array $validated): CreatedOrderData
    {
        if (! $customer->is_active) {
            throw ValidationException::withMessages([
                'account' => 'Tài khoản này hiện không thể thực hiện giao dịch.',
            ]);
        }

        $userId = (int) $customer->getKey();
        $address = ShippingAddressData::fromValidated($validated);

        return DB::transaction(function () use ($cart, $customer, $userId, $validated, $address): CreatedOrderData {
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->getKey());
            $totals = $this->calculateCheckoutTotals->execute(
                cart: $lockedCart,
                address: $address,
                discountCode: $validated['discount_code'] ?? null,
                shippingOption: $validated['shipping_option'],
                lockForUpdate: true,
            );
            $paymentMethod = PaymentMethodCatalog::get($validated['payment_method']);
            $requiresVietQr = (bool) $paymentMethod['requires_vietqr'];
            $placedAt = now();
            $estimatedDeliveryAt = $this->calculateEstimatedDeliveryAt->execute($placedAt, $totals->shipping->estimatedDays);

            $order = Order::query()->create([
                'number' => $this->generateOrderNumber(),
                'user_id' => $userId,
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_status' => $paymentMethod['initial_status'],
                'currency' => config('commerce.currency'),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $customer->email,
                'customer_phone' => $validated['customer_phone'],
                'shipping_recipient_name' => $address->recipientName,
                'shipping_phone' => $address->phone,
                'shipping_address_line_1' => $address->addressLine1,
                'shipping_address_line_2' => $address->addressLine2,
                'shipping_ward' => $address->ward,
                'shipping_district' => $address->district,
                'shipping_city' => $address->city,
                'shipping_postal_code' => $address->postalCode,
                'shipping_country_code' => $address->countryCode,
                'shipping_provider' => $totals->shipping->provider,
                'shipping_service' => $totals->shipping->service,
                'shipping_quote_id' => $totals->shipping->quoteId,
                'shipping_quote_payload' => $totals->shipping->payload,
                'shipping_total_weight_grams' => $totals->shipping->totalWeightGrams,
                'shipping_estimated_days' => $totals->shipping->estimatedDays,
                'shipping_fee_is_estimated' => $totals->shipping->isEstimated,
                'subtotal' => $totals->subtotal,
                'shipping_fee' => $totals->shipping->fee,
                'discount_total' => $totals->discount->amount,
                'total' => $totals->total,
                'customer_note' => $validated['customer_note'] ?? null,
                'placed_at' => $placedAt,
                'estimated_delivery_at' => $estimatedDeliveryAt,
            ]);

            if ($totals->discount->isApplied()) {
                $order->discount()->create($totals->discount->toOrderDiscountAttributes());
                $totals->discount->promotion->increment('usage_count');
            }

            foreach ($totals->lines as $line) {
                $order->items()->create($line->toOrderItemAttributes());

                ProductVariant::query()
                    ->whereKey($line->variantId)
                    ->update([
                        'stock_quantity' => $line->stockQuantity - $line->quantity,
                        'updated_at' => now(),
                    ]);

                DB::table('inventory_movements')->insert([
                    'product_variant_id' => $line->variantId,
                    'order_id' => $order->getKey(),
                    'actor_id' => $userId,
                    'type' => 'order_placed',
                    'quantity' => -$line->quantity,
                    'balance_after' => $line->stockQuantity - $line->quantity,
                    'note' => "Tạo đơn {$order->number}",
                    'created_at' => now(),
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->getKey(),
                'from_status' => null,
                'to_status' => 'pending',
                'changed_by' => $userId,
                'note' => 'Đơn hàng được tạo từ checkout.',
            ]);

            $vietQr = $requiresVietQr ? $this->buildVietQrPayment->execute($order) : null;
            $payment = $order->payments()->create([
                'provider' => $paymentMethod['provider'],
                'provider_reference' => $requiresVietQr ? $order->number : null,
                'amount' => $order->total,
                'currency' => $order->currency,
                'status' => $order->payment_status,
                'payload' => $vietQr?->toArray() ?? $this->paymentPayload($validated['payment_method'], $paymentMethod),
            ]);

            PaymentStatusHistory::query()->create([
                'payment_id' => $payment->getKey(),
                'from_status' => null,
                'to_status' => $payment->status,
                'changed_by' => $userId,
                'note' => 'Thanh toán được khởi tạo từ checkout.',
            ]);

            $lockedCart->items()->delete();

            return new CreatedOrderData(
                order: $order,
                payment: $payment,
                vietQr: $vietQr,
            );
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = config('checkout.order_number_prefix')
                .'-'.now()->format('ymd')
                .'-'.Str::upper(Str::random(7));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }

    /** @param array<string, mixed> $paymentMethod */
    private function paymentPayload(string $paymentMethodCode, array $paymentMethod): ?array
    {
        if (! $paymentMethod['is_simulated']) {
            return null;
        }

        return [
            'payment_method' => $paymentMethodCode,
            'integration_status' => 'pending_gateway_integration',
            'message' => $paymentMethod['confirmation_description'],
        ];
    }
}
