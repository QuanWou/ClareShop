@extends('layouts.storefront', [
    'title' => 'Checkout',
    'description' => 'Hoàn tất đơn hàng Clare bằng COD hoặc chuyển khoản VietQR.',
])

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
                <p>Đơn hàng được gắn với tài khoản Clare của bạn. Phí vận chuyển được ước tính theo địa chỉ và trọng lượng đơn.</p>
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
                    </section>

                    <section class="checkout-section" aria-labelledby="checkout-payment-title">
                        <p class="eyebrow">03 / Thanh toán</p>
                        <h2 id="checkout-payment-title">Chọn phương thức</h2>

                        <div class="payment-methods">
                            <label class="payment-method">
                                <input name="payment_method" type="radio" value="cod" @checked(old('payment_method', 'cod') === 'cod')>
                                <span>
                                    <strong>Thanh toán khi nhận hàng</strong>
                                    <small>Thanh toán trực tiếp khi đơn được giao.</small>
                                </span>
                            </label>

                            <label class="payment-method">
                                <input name="payment_method" type="radio" value="bank_transfer" @checked(old('payment_method') === 'bank_transfer')>
                                <span>
                                    <strong>Chuyển khoản qua VietQR</strong>
                                    <small>Mã QR đúng số tiền và nội dung chuyển khoản sẽ hiện sau khi đặt đơn.</small>
                                </span>
                            </label>
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

                    <p class="checkout-quote-status" aria-live="polite" data-checkout-quote-status>Hoàn thiện địa chỉ để hệ thống tính phí ship, ngày nhận dự kiến và kiểm tra mã ưu đãi.</p>
                    <button class="checkout-quote-button" type="button" data-checkout-quote>Tính phí ship và áp dụng ưu đãi</button>
                    <button class="button button-primary button-wide" type="submit">Đặt đơn hàng</button>
                    <p class="checkout-security-note">Phí ship hiện là ước tính nội bộ theo địa chỉ và khối lượng; chưa phải báo giá của GHN/GHTK. Tổng tiền và ưu đãi luôn được tính lại tại máy chủ khi đặt đơn.</p>
                </aside>
            </form>
        </div>
    </section>
@endsection
