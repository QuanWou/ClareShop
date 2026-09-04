@extends('layouts.storefront', [
    'title' => 'Checkout',
    'description' => 'Hoàn tất đơn hàng Clare với lựa chọn vận chuyển và thanh toán phù hợp.',
])

@php
    $selectedShippingOption = old('shipping_option', $defaultShippingOption);
    $selectedPaymentMethod = old('payment_method', 'cod');
    $selectedDiscountCode = old('discount_code', $selectedDiscountCode);
    $fallbackSavedAddress = $savedAddresses->firstWhere('is_default', true) ?? $savedAddresses->first();
    $selectedSavedAddressId = old('saved_address', $fallbackSavedAddress?->getKey() ?? 'custom');
    $selectedSavedAddress = (string) $selectedSavedAddressId === 'custom'
        ? null
        : ($savedAddresses->firstWhere('id', (int) $selectedSavedAddressId) ?? $fallbackSavedAddress);
    $initialShipping = $initialQuote?->shipping;
    $initialDiscount = $initialQuote?->discount;
    $initialShippingOptions = collect($initialQuote?->shippingOptions ?? [])->mapWithKeys(fn ($quote) => [$quote->toArray()['option'] => $quote]);
    $addressCopy = static function ($address): string {
        return collect([$address?->address_line_1, $address?->address_line_2, $address?->ward, $address?->district, $address?->city])->filter(fn ($part) => filled($part))->join(', ');
    };
@endphp

@section('content')
    <section class="checkout-page section" aria-labelledby="checkout-title">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Đường dẫn"><a href="{{ route('catalog.home') }}">Trang chủ</a><span aria-hidden="true">/</span><a href="{{ route('cart.show') }}">Giỏ hàng</a><span aria-hidden="true">/</span><span aria-current="page">Thanh toán</span></nav>
            <div class="checkout-heading checkout-heading-compact"><div><p class="eyebrow">Bước cuối cùng</p><h1 id="checkout-title">Hoàn tất đơn hàng</h1></div><p>Kiểm tra lại địa chỉ, vận chuyển và phương thức thanh toán trước khi đặt đơn.</p></div>

            <form id="checkout-form" class="checkout-layout checkout-layout-shopee" action="{{ route('checkout.store') }}" method="POST" data-checkout-form data-quote-url="{{ route('checkout.quote') }}" data-has-initial-quote="{{ $initialQuote ? 'true' : 'false' }}">
                @csrf
                <input type="hidden" name="saved_address" value="{{ $selectedSavedAddressId }}" data-selected-saved-address>
                <div class="checkout-fields">
                    <section class="checkout-section checkout-address-section" aria-labelledby="checkout-shipping-title">
                        <div class="checkout-section-head"><div><p class="eyebrow">01 / Giao hàng</p><h2 id="checkout-shipping-title">Địa chỉ nhận hàng</h2></div><span class="checkout-section-hint">Giao tận nơi tại Việt Nam</span></div>
                        <div class="checkout-address-summary" data-address-summary>
                            <button class="checkout-address-summary-main" type="button" data-address-summary-toggle aria-expanded="false" aria-controls="checkout-address-summary-details">
                                <span class="checkout-address-pin" aria-hidden="true">●</span>
                                <span class="checkout-address-summary-copy-block">
                                    <strong data-address-summary-label>{{ $selectedSavedAddress?->label ?? 'Chưa chọn địa chỉ' }}</strong>
                                    <span class="checkout-address-summary-brief" data-address-summary-brief>{{ $selectedSavedAddress ? collect([$selectedSavedAddress->district, $selectedSavedAddress->city])->filter()->join(', ') : 'Chọn nơi nhận hàng để xem phí vận chuyển.' }}</span>
                                </span>
                                <span class="checkout-address-expand" data-address-summary-expand>Xem chi tiết</span>
                            </button>
                            <div class="checkout-address-meta">
                                @if ($selectedSavedAddress?->is_default)<small class="checkout-default-badge">Mặc định</small>@endif
                                <button class="checkout-address-change" type="button" data-address-picker-open aria-haspopup="dialog">{{ $selectedSavedAddress ? 'Thay đổi' : 'Chọn địa chỉ' }}</button>
                            </div>
                            <div class="checkout-address-summary-details" id="checkout-address-summary-details" data-address-summary-details hidden>
                                <div><span>Người nhận</span><p><strong data-address-summary-recipient>{{ $selectedSavedAddress?->recipient_name ?? 'Chưa cập nhật' }}</strong><span data-address-summary-phone>{{ $selectedSavedAddress?->phone }}</span></p></div>
                                <div><span>Địa chỉ đầy đủ</span><p data-address-summary-copy>{{ $selectedSavedAddress ? $addressCopy($selectedSavedAddress) : 'Chưa có địa chỉ nhận hàng.' }}</p></div>
                            </div>
                        </div>
                        {{-- Các trường này chỉ gửi snapshot địa chỉ tới server; không lặp lại thông tin người nhận ngoài giao diện. --}}
                        <div class="checkout-hidden-address-fields is-address-collapsed" data-address-form-fields aria-hidden="true"><input name="shipping_recipient_name" value="{{ old('shipping_recipient_name', $defaultAddress?->recipient_name ?? $customer->name) }}" data-shipping-field><input name="shipping_phone" value="{{ old('shipping_phone', $defaultAddress?->phone ?? $customer->phone) }}" data-shipping-field><input name="shipping_city" value="{{ old('shipping_city', $defaultAddress?->city) }}" data-shipping-field><input name="shipping_address_line_1" value="{{ old('shipping_address_line_1', $defaultAddress?->address_line_1) }}" data-shipping-field><input name="shipping_address_line_2" value="{{ old('shipping_address_line_2', $defaultAddress?->address_line_2) }}" data-shipping-field><input name="shipping_ward" value="{{ old('shipping_ward', $defaultAddress?->ward) }}" data-shipping-field><input name="shipping_district" value="{{ old('shipping_district', $defaultAddress?->district) }}" data-shipping-field><input name="shipping_postal_code" value="{{ old('shipping_postal_code', $defaultAddress?->postal_code) }}" data-shipping-field><input name="shipping_country_code" value="VN" data-shipping-field></div>
                    </section>

                    <section class="checkout-section checkout-products-section" aria-labelledby="checkout-products-title"><div class="checkout-section-head"><div><p class="eyebrow">02 / Sản phẩm</p><h2 id="checkout-products-title">Sản phẩm trong đơn</h2></div><span class="checkout-products-count">{{ $cartLines->sum(fn ($line) => $line['item']->quantity) }} sản phẩm</span></div><div class="checkout-products-table" role="table" aria-label="Sản phẩm checkout"><div class="checkout-products-table-head" role="row"><span>Sản phẩm</span><span>Đơn giá</span><span>Số lượng</span><span>Thành tiền</span></div>@foreach ($cartLines as $line)<article class="checkout-product-row" role="row"><div class="checkout-product-main"><img src="{{ $line['variant']->imageUrl() }}" alt="{{ $line['product']->name }}" loading="lazy"><div><strong>{{ $line['product']->name }}</strong><small>{{ $line['variant']->color_name }} · SKU {{ $line['variant']->sku }}</small></div></div><span>{{ \App\Modules\Shared\Support\Money::formatVnd($line['unit_price']) }}</span><span>×{{ $line['item']->quantity }}</span><strong>{{ \App\Modules\Shared\Support\Money::formatVnd($line['line_total']) }}</strong></article>@endforeach</div></section>

                    <section class="checkout-section checkout-voucher-section" aria-labelledby="checkout-voucher-title"><div class="checkout-inline-action"><div><p class="eyebrow">03 / Ưu đãi</p><h2 id="checkout-voucher-title">Voucher của Clare</h2><p class="checkout-inline-muted" data-checkout-voucher-summary>@if($initialDiscount?->isApplied()) Đã chọn mã {{ $initialDiscount->code }} · giảm {{ \App\Modules\Shared\Support\Money::formatVnd($initialDiscount->amount) }} @else Chọn một mã giảm giá phù hợp với đơn hàng. @endif</p></div><button type="button" class="checkout-outline-button" data-voucher-picker-open aria-haspopup="dialog">Chọn voucher</button></div><input class="checkout-voucher-hidden-input" name="discount_code" value="{{ $selectedDiscountCode }}" maxlength="50" autocomplete="off" data-checkout-discount-code><p class="checkout-discount-feedback" id="checkout-discount-feedback" aria-live="polite" hidden data-checkout-discount-feedback></p></section>

                    <section class="checkout-section checkout-note-shipping-section" aria-labelledby="checkout-shipping-option-title"><div class="checkout-note-row"><label class="checkout-field"><span>Lời nhắn cho người bán <small>Không bắt buộc</small></span><input name="customer_note" value="{{ old('customer_note') }}" maxlength="2000" placeholder="Lưu ý cho Clare…"></label></div><div class="checkout-shipping-options" aria-labelledby="checkout-shipping-option-title"><div class="checkout-section-head"><div><p class="eyebrow">04 / Vận chuyển</p><h2 id="checkout-shipping-option-title">Phương án giao hàng</h2></div><p class="checkout-inline-muted">Phí và ngày giao thay đổi theo địa chỉ, khối lượng và đơn vị bạn chọn.</p></div><div class="shipping-methods">@foreach ($shippingOptions as $shippingOption) @php($optionQuote = $initialShippingOptions->get($shippingOption['code']))<label class="shipping-method" data-shipping-option-card="{{ $shippingOption['code'] }}"><input name="shipping_option" type="radio" value="{{ $shippingOption['code'] }}" @checked($selectedShippingOption === $shippingOption['code']) data-shipping-option><span class="shipping-method-copy"><strong>{{ $shippingOption['label'] }}</strong><small>{{ $shippingOption['service'] }} · {{ $shippingOption['description'] }}</small></span><span class="shipping-method-quote"><strong data-shipping-option-price="{{ $shippingOption['code'] }}">{{ $optionQuote ? \App\Modules\Shared\Support\Money::formatVnd($optionQuote->fee) : 'Nhập địa chỉ' }}</strong><small data-shipping-option-eta="{{ $shippingOption['code'] }}">{{ $optionQuote ? ($optionQuote->estimatedDeliveryAt?->locale('vi')->isoFormat('dddd, DD/MM') ?? 'Đang cập nhật') : 'để xem phí' }}</small></span></label>@endforeach</div></div></section>

                    <section class="checkout-section checkout-payment-section" aria-labelledby="checkout-payment-title"><div class="checkout-section-head"><div><p class="eyebrow">05 / Thanh toán</p><h2 id="checkout-payment-title">Phương thức thanh toán</h2></div><span class="checkout-section-hint">Thông tin được bảo mật</span></div><div class="payment-methods">@foreach ($paymentMethods as $code => $paymentMethod)<label class="payment-method"><input name="payment_method" type="radio" value="{{ $code }}" @checked($selectedPaymentMethod === $code)><span><strong>{{ $paymentMethod['label'] }}</strong><small>{{ $paymentMethod['description'] }}</small>@if ($paymentMethod['requires_qr'])<small class="payment-method-pending">Mã QR có hiệu lực 3 phút sau khi đặt đơn.</small>@elseif ($paymentMethod['is_simulated'])<small class="payment-method-pending">Đang chờ kết nối cổng thanh toán.</small>@endif</span></label>@endforeach</div></section>
                </div>

                <aside class="checkout-summary checkout-summary-sticky" aria-labelledby="checkout-summary-title"><div class="checkout-summary-top"><div><p class="eyebrow">06 / Xác nhận</p><h2 id="checkout-summary-title">Tổng thanh toán</h2></div><span class="checkout-summary-lock">Clare secure</span></div><div class="checkout-summary-lines"><div><span>Tạm tính</span><strong data-checkout-subtotal>{{ \App\Modules\Shared\Support\Money::formatVnd($subtotal) }}</strong></div><div><span>Phí giao hàng</span><strong data-checkout-shipping>{{ $initialShipping ? \App\Modules\Shared\Support\Money::formatVnd($initialShipping->fee) : 'Nhập địa chỉ để ước tính' }}</strong></div><div><span>Ngày nhận dự kiến</span><strong data-checkout-eta>{{ $initialShipping?->estimatedDeliveryAt?->locale('vi')->isoFormat('dddd, DD/MM') ?? 'Hoàn thiện địa chỉ để xem' }}</strong></div><div class="checkout-discount-total"><span>Giảm giá voucher</span><strong data-checkout-discount>@if($initialDiscount?->isApplied()) -{{ \App\Modules\Shared\Support\Money::formatVnd($initialDiscount->amount) }} @else — @endif</strong></div><div class="checkout-total"><span>Tổng thanh toán</span><strong data-checkout-total>{{ \App\Modules\Shared\Support\Money::formatVnd($initialQuote?->total ?? $subtotal) }}</strong></div></div><dl class="checkout-shipping-details" @if (! $initialShipping) hidden @endif data-checkout-shipping-details><div><dt>Đơn vị</dt><dd data-checkout-shipping-provider>{{ $initialShipping?->provider ?? '—' }}</dd></div><div><dt>Dịch vụ</dt><dd data-checkout-shipping-service>{{ $initialShipping?->service ?? '—' }}</dd></div><div><dt>Khối lượng</dt><dd data-checkout-shipping-weight>{{ $initialShipping ? number_format($initialShipping->totalWeightGrams, 0, ',', '.').' g' : '—' }}</dd></div><div><dt>Cách tính</dt><dd data-checkout-shipping-rule>{{ $initialShipping ? 'Theo địa chỉ, khối lượng và đơn vị vận chuyển.' : '—' }}</dd></div></dl><p class="checkout-quote-status" aria-live="polite" data-checkout-quote-status>{{ $initialShipping ? 'Phí giao hàng và ngày nhận đã được ước tính theo địa chỉ đã lưu.' : 'Chọn địa chỉ để so sánh phí GHN, GHTK và J&T Express.' }}</p><button class="checkout-quote-button" type="button" data-checkout-quote>Kiểm tra lại phí giao hàng</button><button class="button button-primary button-wide checkout-submit-button" type="submit">Đặt đơn hàng</button><p class="checkout-security-note">{{ $siteSettings->get('shipping_note') }} {{ $siteSettings->get('payment_note') }} Tổng tiền được tính lại tại máy chủ khi đặt đơn.</p></aside>
            </form>

            <dialog class="checkout-modal checkout-address-dialog" data-address-picker-dialog aria-labelledby="checkout-address-dialog-title">
                <div class="checkout-modal-heading">
                    <div><p class="eyebrow">Địa chỉ của tôi</p><h3 id="checkout-address-dialog-title">Chọn nơi nhận hàng</h3></div>
                    <button type="button" class="checkout-modal-close" data-address-picker-close aria-label="Đóng">×</button>
                </div>
                <div class="checkout-modal-body checkout-saved-addresses" aria-label="Địa chỉ đã lưu">
                    @forelse ($savedAddresses as $address)
                        <div class="checkout-address-item" data-address-item="{{ $address->getKey() }}">
                            <label class="checkout-saved-address">
                                <input name="checkout_address_choice" type="radio" value="{{ $address->getKey() }}" @checked((string) $selectedSavedAddressId === (string) $address->getKey()) data-saved-address data-address-label="{{ $address->label }}" data-address-copy="{{ $addressCopy($address) }}" data-recipient-name="{{ $address->recipient_name }}" data-phone="{{ $address->phone }}" data-address-line-1="{{ $address->address_line_1 }}" data-address-line-2="{{ $address->address_line_2 }}" data-ward="{{ $address->ward }}" data-district="{{ $address->district }}" data-city="{{ $address->city }}" data-postal-code="{{ $address->postal_code }}" data-is-default="{{ $address->is_default ? 'true' : 'false' }}">
                                <span class="checkout-saved-address-card"><span><strong data-address-option-label>{{ $address->label }} @if ($address->is_default)<small class="checkout-default-badge">Mặc định</small>@endif</strong><small data-address-option-area>{{ collect([$address->district, $address->city])->filter()->join(', ') }}</small></span><i>Chọn</i></span>
                            </label>
                            <button class="checkout-address-details-toggle" type="button" data-address-details-toggle="{{ $address->getKey() }}" aria-expanded="false" aria-controls="checkout-address-details-{{ $address->getKey() }}">Xem chi tiết</button>
                            <div class="checkout-address-item-details" id="checkout-address-details-{{ $address->getKey() }}" data-address-item-details="{{ $address->getKey() }}" hidden>
                                <div><span>Người nhận</span><strong data-address-option-recipient>{{ $address->recipient_name }} · {{ $address->phone }}</strong></div>
                                <div><span>Địa chỉ đầy đủ</span><p data-address-option-copy>{{ $addressCopy($address) }}</p></div>
                            </div>
                            <div class="checkout-address-item-actions"><button type="button" data-address-edit="{{ $address->getKey() }}">Sửa</button>@unless($address->is_default)<button type="button" data-address-default="{{ $address->getKey() }}" data-address-default-url="{{ route('account.addresses.default', $address) }}">Đặt mặc định</button>@endunless</div>
                            <div class="checkout-address-edit-panel" data-address-edit-panel="{{ $address->getKey() }}" hidden>@include('customers.account.partials.address-form', ['address' => $address, 'formAction' => route('account.addresses.update', $address), 'formMethod' => 'PATCH', 'submitLabel' => 'Lưu địa chỉ'])</div>
                        </div>
                    @empty
                        <p class="checkout-modal-empty">Bạn chưa có địa chỉ đã lưu. Có thể nhập địa chỉ dùng riêng cho đơn này ngay bên dưới.</p>
                    @endforelse

                    <label class="checkout-saved-address checkout-custom-address">
                        <input name="checkout_address_choice" type="radio" value="custom" @checked((string) $selectedSavedAddressId === 'custom') data-saved-address data-custom-address-choice>
                        <span class="checkout-saved-address-card"><span><strong>Địa chỉ khác cho đơn này</strong><small>Nhập ngay trong cửa sổ này, không bắt buộc lưu.</small></span><i>Nhập địa chỉ</i></span>
                    </label>
                    <div class="checkout-custom-address-panel" data-custom-address-panel @if ((string) $selectedSavedAddressId !== 'custom') hidden @endif>
                        <div class="checkout-custom-address-grid">
                            <label><span>Tên người nhận</span><input value="{{ old('shipping_recipient_name', $customer->name) }}" maxlength="255" autocomplete="shipping name" required data-custom-address-field="shipping_recipient_name"></label>
                            <label><span>Số điện thoại</span><input type="tel" value="{{ old('shipping_phone', $customer->phone) }}" maxlength="30" autocomplete="shipping tel" required data-custom-address-field="shipping_phone"></label>
                            <label class="checkout-custom-address-full"><span>Địa chỉ</span><input value="{{ old('shipping_address_line_1') }}" maxlength="255" autocomplete="shipping street-address" placeholder="Số nhà, tên đường" required data-custom-address-field="shipping_address_line_1"></label>
                            <label><span>Phường / Xã</span><input value="{{ old('shipping_ward') }}" maxlength="255" required data-custom-address-field="shipping_ward"></label>
                            <label><span>Quận / Huyện</span><input value="{{ old('shipping_district') }}" maxlength="255" required data-custom-address-field="shipping_district"></label>
                            <label><span>Tỉnh / Thành phố</span><input value="{{ old('shipping_city') }}" maxlength="255" autocomplete="shipping address-level1" required data-custom-address-field="shipping_city"></label>
                            <label><span>Mã bưu chính <small>Không bắt buộc</small></span><input value="{{ old('shipping_postal_code') }}" maxlength="20" data-custom-address-field="shipping_postal_code"></label>
                            <label class="checkout-custom-address-full"><span>Địa chỉ bổ sung <small>Không bắt buộc</small></span><input value="{{ old('shipping_address_line_2') }}" maxlength="255" data-custom-address-field="shipping_address_line_2"></label>
                        </div>
                    </div>

                    <button type="button" class="checkout-add-address-button" data-address-new-toggle>+ Lưu địa chỉ mới vào tài khoản</button>
                    <div class="checkout-address-new-panel" data-address-new-panel hidden>@include('customers.account.partials.address-form', ['address' => null, 'formAction' => route('account.addresses.store'), 'formMethod' => 'POST', 'submitLabel' => 'Thêm địa chỉ'])</div>
                </div>
                <div class="checkout-modal-actions"><button type="button" class="button button-light" data-address-picker-close>Hủy</button><button type="button" class="button button-primary" data-address-picker-confirm>Dùng địa chỉ đã chọn</button></div>
            </dialog>

            <dialog class="checkout-modal checkout-voucher-dialog" data-voucher-picker-dialog aria-labelledby="checkout-voucher-dialog-title"><div class="checkout-modal-heading"><div><p class="eyebrow">Ưu đãi Clare</p><h3 id="checkout-voucher-dialog-title">Chọn voucher</h3></div><button type="button" class="checkout-modal-close" data-voucher-picker-close aria-label="Đóng">×</button></div><div class="checkout-modal-body"><div class="checkout-voucher-code-entry"><label for="checkout-voucher-code-input">Mã voucher</label><div><input id="checkout-voucher-code-input" value="{{ $selectedDiscountCode }}" maxlength="50" placeholder="Nhập mã Clare" data-voucher-code-input><button type="button" class="checkout-outline-button" data-voucher-apply>Áp dụng</button></div><p data-voucher-feedback aria-live="polite"></p></div><div class="checkout-voucher-list">@forelse ($voucherOptions as $option) @php($voucher = $option['voucher']) @php($promotion = $voucher->promotionCode)<button class="checkout-voucher-option" type="button" data-checkout-voucher-code="{{ $promotion->code }}" data-voucher-eligible="{{ $option['eligible'] ? 'true' : 'false' }}" @disabled(! $option['eligible'])><span class="checkout-voucher-ticket">{{ $promotion->discount_type === 'percentage' ? '％' : '₫' }}</span><span><strong>{{ $promotion->code }}</strong><b>{{ $promotion->name }}</b><small>{{ $option['eligible'] ? 'Giảm '.\App\Modules\Shared\Support\Money::formatVnd($option['amount']) : $option['reason'] }}</small><em>Đơn tối thiểu {{ \App\Modules\Shared\Support\Money::formatVnd((int) $promotion->minimum_order_amount) }} · @if($promotion->ends_at) Hết {{ $promotion->ends_at->format('d/m/Y') }} @else Không giới hạn @endif</em></span><i>Chọn</i></button>@empty<p class="checkout-modal-empty">Bạn chưa lưu voucher nào. <a href="{{ route('promotions.index') }}">Xem ưu đãi công khai</a></p>@endforelse</div></div><div class="checkout-modal-actions checkout-voucher-actions"><button type="button" class="button button-light" data-voucher-picker-close>Trở lại</button><button type="button" class="button button-primary" data-voucher-confirm>Đồng ý</button></div></dialog>
        </div>
    </section>
@endsection
