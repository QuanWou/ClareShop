<?php

namespace App\Modules\Orders\Http\Requests;

use App\Modules\Orders\Http\Requests\Concerns\HasShippingAddressRules;
use App\Modules\Orders\Support\ShippingOptionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteCheckoutRequest extends FormRequest
{
    use HasShippingAddressRules;

    public function authorize(): bool
    {
        return $this->user()?->is_active === true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeSavedAddress();

        if (! $this->filled('shipping_option')) {
            $this->merge(['shipping_option' => ShippingOptionCatalog::defaultCode()]);
        }
    }

    /**
     * Use a selected address-book entry as the source of truth for checkout.
     * The address is always scoped to the authenticated customer, so a posted
     * id can never read another customer's address.
     */
    private function mergeSavedAddress(): void
    {
        $savedAddressId = $this->input('saved_address');

        if (! filled($savedAddressId) || $savedAddressId === 'custom' || ! $this->user()) {
            return;
        }

        $address = $this->user()->addresses()->whereKey($savedAddressId)->first();

        if ($address === null) {
            return;
        }

        $this->merge([
            'shipping_recipient_name' => $address->recipient_name,
            'shipping_phone' => $address->phone,
            'shipping_address_line_1' => $address->address_line_1,
            'shipping_address_line_2' => $address->address_line_2,
            'shipping_ward' => $address->ward,
            'shipping_district' => $address->district,
            'shipping_city' => $address->city,
            'shipping_postal_code' => $address->postal_code,
            'shipping_country_code' => $address->country_code,
        ]);
    }

    public function rules(): array
    {
        return [
            ...$this->shippingAddressRules(),
            'shipping_option' => ['required', 'string', Rule::in(ShippingOptionCatalog::codes())],
            'discount_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            ...$this->shippingAddressMessages(),
            'discount_code.max' => 'Mã ưu đãi không được vượt quá 50 ký tự.',
        ];
    }
}
