@extends('layouts.storefront', [
    'title' => 'Checkout',
    'description' => 'Hoàn tất đơn hàng Clare với lựa chọn vận chuyển và thanh toán phù hợp.',
])

@php
    $selectedShippingOption = old('shipping_option', $defaultShippingOption);
    $selectedPaymentMethod = old('payment_method', 'cod');
@endphp

@section('content')
    <section class="checkout-page section" aria-labelledby="checkout-title">
        <div class="shell">
            <nav class="breadcrumbs" aria-label="Đường dẫn">
                <a href="{{ route('catalog.home') }}">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('cart.show') }}">Giỏ hàng</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Checkout</span>
            </nav>

            <div class="checkout-heading">
                <div>
                    <p class="eyebrow">Bước cuối cùng</p>
                    <h1 id="checkout-title">Hoàn tất<br>đơn hàng.</h1>
                </div>
                <p>Đơn hàng được gắn với tài khoản Clare của bạn. Chọn đơn vị vận chuyển, xem phí ước tính theo địa chỉ và chốt phương thức thanh toán phù hợp.</p>
            </div>

            <form
                class="checkout-layout"
                action="{{ route('checkout.store') }}"
                method="POST"
                data-checkout-form
                data-quote-url="{{ route('checkout.quote') }}"
            >
                @csrf

                <div class="checkout-fields">
                    <section class="checkout-section" aria-labelledby="checkout-contact-title">
                        <p class="eyebrow">01 / Liên hệ</p>
                        <h2 id="checkout-contact-title">Thông tin tài khoản</h2>

                        <div class="checkout-form-grid">
                            <label class="checkout-field checkout-field-full">
                                <span>Họ và tên</span>
                                <input name="customer_name" value="{{ old('customer_name', $customer->name) }}" autocomplete="name" required>
                            </label>

                            <div class="checkout-field">
                                <span>Email</span>
                                <p class="checkout-account-email">{{ $customer->email }}</p>
                            </div>

                            <label class="checkout-field">
                                <span>Số điện thoại</span>
                                <input name="customer_phone" type="tel" value="{{ old('customer_phone', $customer->phone) }}" autocomplete="tel" required>
                            </label>
                        </div>
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-shipping-title">
                        <p class="eyebrow">02 / Giao hàng</p>
                        <h2 id="checkout-shipping-title">Địa chỉ nhận hàng</h2>

                        <div class="checkout-form-grid">
                            <label class="checkout-field checkout-field-full">
                                <span>Tên người nhận</span>
                                <input name="shipping_recipient_name" value="{{ old('shipping_recipient_name', $defaultAddress?->recipient_name ?? old('customer_name', $customer->name)) }}" autocomplete="shipping name" required data-shipping-field>
                            </label>

                            <label class="checkout-field">
                                <span>Số điện thoại nhận hàng</span>
                                <input name="shipping_phone" type="tel" value="{{ old('shipping_phone', $defaultAddress?->phone ?? old('customer_phone', $customer->phone)) }}" autocomplete="shipping tel" required data-shipping-field>
                            </label>

                            <label class="checkout-field">
                                <span>Tỉnh / Thành phố</span>
                                <input name="shipping_city" value="{{ old('shipping_city', $defaultAddress?->city) }}" autocomplete="shipping address-level1" required data-shipping-field>
                            </label>

                            <label class="checkout-field checkout-field-full">
                                <span>Địa chỉ</span>
                                <input name="shipping_address_line_1" value="{{ old('shipping_address_line_1', $defaultAddress?->address_line_1) }}" autocomplete="shipping street-address" placeholder="Số nhà, tên đường" required data-shipping-field>
                            </label>

                            <label class="checkout-field">
                                <span>Phường / Xã</span>
                                <input name="shipping_ward" value="{{ old('shipping_ward', $defaultAddress?->ward) }}" required data-shipping-field>
                            </label>

                            <label class="checkout-field">
                                <span>Quận / Huyện</span>
                                <input name="shipping_district" value="{{ old('shipping_district', $defaultAddress?->district) }}" required data-shipping-field>
                            </label>

                            <label class="checkout-field">
                                <span>Mã bưu chính <small>Không bắt buộc</small></span>
                                <input name="shipping_postal_code" value="{{ old('shipping_postal_code', $defaultAddress?->postal_code) }}" autocomplete="shipping postal-code" maxlength="20" data-shipping-field>
                            </label>

                            <label class="checkout-field checkout-field-full">
                                <span>Địa chỉ bổ sung <small>Không bắt buộc</small></span>
                                <input name="shipping_address_line_2" value="{{ old('shipping_address_line_2', $defaultAddress?->address_line_2) }}" autocomplete="shipping address-line2">
                            </label>
                        </div>

                        <input name="shipping_country_code" type="hidden" value="VN" data-shipping-field>

                        <div class="checkout-shipping-options" aria-labelledby="checkout-shipping-option-title">
                            <div class="checkout-shipping-options-heading">
                                <div>
                                    <p class="eyebrow">Chọn đơn vị</p>
                                    <h3 id="checkout-shipping-option-title">Phương án giao hàng</h3>
                                </div>
                                <p>Phí và ngày giao thay đổi theo địa chỉ, khối lượng và đơn vị bạn chọn.</p>
                            </div>

                            <div class="shipping-methods">
                                @foreach ($shippingOptions as $shippingOption)
                                    <label class="shipping-method" data-shipping-option-card="{{ $shippingOption['code'] }}">
                                        <input name="shipping_option" type="radio" value="{{ $shippingOption['code'] }}" @checked($selectedShippingOption === $shippingOption['code']) data-shipping-option>
                                        <span class="shipping-method-copy">
                                            <strong>{{ $shippingOption['label'] }}</strong>
                                            <small>{{ $shippingOption['service'] }} · {{ $shippingOption['description'] }}</small>
                                        </span>
                                        <span class="shipping-method-quote">
                                            <strong data-shipping-option-price="{{ $shippingOption['code'] }}">Nhập địa chỉ</strong>
                                            <small data-shipping-option-eta="{{ $shippingOption['code'] }}">để xem phí</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-payment-title">
                        <p class="eyebrow">03 / Thanh toán</p>
                        <h2 id="checkout-payment-title">Chọn phương thức</h2>

                        <div class="payment-methods">
                            @foreach ($paymentMethods as $code => $paymentMethod)
                                <label class="payment-method">
                                    <input name="payment_method" type="radio" value="{{ $code }}" @checked($selectedPaymentMethod === $code)>
                                    <span>
                                        <strong>{{ $paymentMethod['label'] }}</strong>
                                        <small>{{ $paymentMethod['description'] }}</small>
                                        @if ($paymentMethod['is_simulated'])
                                            <small class="payment-method-pending">Chờ kết nối cổng thanh toán/đối tác chính thức.</small>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <label class="checkout-field checkout-note-field">
                            <span>Ghi chú cho đơn hàng <small>Không bắt buộc</small></span>
                            <textarea name="customer_note" rows="4" maxlength="2000">{{ old('customer_note') }}</textarea>
                        </label>
                    </section>
                </div>

                <aside class="checkout-summary" aria-labelledby="checkout-summary-title">
                    <p class="eyebrow">Tóm tắt đơn hàng</p>
                    <h2 id="checkout-summary-title">Bạn đã chọn</h2>

                    <div class="checkout-summary-lines">
                        @foreach ($cartLines as $line)
                            <div>
                                <span>{{ $line['product']->name }} · {{ $line['variant']->color_name }} × {{ $line['item']->quantity }}</span>
                                <strong>{{ \App\Modules\Shared\Support\Money::formatVnd($line['line_total']) }}</strong>
                            </div>
                        @endforeach
                    </div>

                    <label class="checkout-field checkout-discount-field">
                        <span>Mã ưu đãi <small>Không bắt buộc</small></span>
                        <input name="discount_code" value="{{ old('discount_code') }}" maxlength="50" autocomplete="off" placeholder="Ví dụ: CLARE10" aria-describedby="checkout-discount-help checkout-discount-feedback" data-checkout-discount-code>
                    </label>
                    <p class="checkout-discount-help" id="checkout-discount-help">Mã được tự kiểm tra khi bạn nhập. Ưu đãi chỉ áp dụng cho tiền hàng, không áp dụng phí vận chuyển.</p>
                    <p class="checkout-discount-feedback" id="checkout-discount-feedback" aria-live="polite" hidden data-checkout-discount-feedback></p>

                    <dl class="checkout-totals">
                        <div>
                            <dt>Tạm tính</dt>
                            <dd data-checkout-subtotal>{{ \App\Modules\Shared\Support\Money::formatVnd($subtotal) }}</dd>
                        </div>
                        <div>
                            <dt>Phí giao hàng</dt>
                            <dd data-checkout-shipping>Nhập địa chỉ để ước tính</dd>
                        </div>
                        <div>
                            <dt>Ngày nhận dự kiến</dt>
                            <dd data-checkout-eta>Hoàn thiện địa chỉ để xem</dd>
                        </div>
                        <div class="checkout-discount-total">
                            <dt>Ưu đãi</dt>
                            <dd data-checkout-discount>—</dd>
                        </div>
                        <div class="checkout-total">
                            <dt>Tổng thanh toán</dt>
                            <dd data-checkout-total>{{ \App\Modules\Shared\Support\Money::formatVnd($subtotal) }}</dd>
                        </div>
                    </dl>

                    <dl class="checkout-shipping-details" hidden data-checkout-shipping-details>
                        <div>
                            <dt>Đơn vị vận chuyển</dt>
                            <dd data-checkout-shipping-provider>—</dd>
                        </div>
                        <div>
                            <dt>Dịch vụ</dt>
                            <dd data-checkout-shipping-service>—</dd>
                        </div>
                        <div>
                            <dt>Khối lượng đơn</dt>
                            <dd data-checkout-shipping-weight>—</dd>
                        </div>
                        <div>
                            <dt>Cách tính</dt>
                            <dd data-checkout-shipping-rule>—</dd>
                        </div>
                    </dl>

                    <p class="checkout-quote-status" aria-live="polite" data-checkout-quote-status>Hoàn thiện địa chỉ để so sánh phí GHN, GHTK, J&amp;T Express, xem ngày nhận dự kiến và kiểm tra mã ưu đãi.</p>
                    <button class="checkout-quote-button" type="button" data-checkout-quote>So sánh phí giao hàng và áp dụng ưu đãi</button>
                    <button class="button button-primary button-wide" type="submit">Đặt đơn hàng</button>
                    <p class="checkout-security-note">Phí ship hiện là ước tính nội bộ theo địa chỉ, khối lượng và đơn vị đã chọn; chưa phải báo giá chính thức của GHN, GHTK hoặc J&amp;T Express. Tổng tiền và ưu đãi luôn được tính lại tại máy chủ khi đặt đơn.</p>
                </aside>
            </form>
        </div>
    </section>
@endsection
