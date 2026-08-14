<?php

namespace App\Modules\Orders\Data;

readonly class ShippingAddressData
{
    public function __construct(
        public string $recipientName,
        public string $phone,
        public string $addressLine1,
        public ?string $addressLine2,
        public string $ward,
        public string $district,
        public string $city,
        public ?string $postalCode,
        public string $countryCode,
    ) {}

    public static function fromValidated(array $validated): self
    {
        return new self(
            recipientName: $validated['shipping_recipient_name'],
            phone: $validated['shipping_phone'],
            addressLine1: $validated['shipping_address_line_1'],
            addressLine2: $validated['shipping_address_line_2'] ?? null,
            ward: $validated['shipping_ward'],
            district: $validated['shipping_district'],
            city: $validated['shipping_city'],
            postalCode: $validated['shipping_postal_code'] ?? null,
            countryCode: $validated['shipping_country_code'],
        );
    }

    public function toArray(): array
    {
        return [
            'recipient_name' => $this->recipientName,
            'phone' => $this->phone,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'ward' => $this->ward,
            'district' => $this->district,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'country_code' => $this->countryCode,
        ];
    }
}
