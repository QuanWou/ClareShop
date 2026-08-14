@extends('layouts.storefront', [
    'title' => 'Tư vấn và lắp đặt',
    'description' => 'Gửi yêu cầu tư vấn ánh sáng hoặc lắp đặt đèn cùng Clare.',
])

@section('content')
    <section class="appointment-page section" aria-labelledby="appointment-title">
        <div class="shell appointment-shell">
            <nav class="breadcrumbs" aria-label="Đường dẫn">
                <a href="{{ route('catalog.home') }}">Trang chủ</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Tư vấn &amp; lắp đặt</span>
            </nav>

            <div class="appointment-heading">
                <div>
                    <p class="eyebrow">Dịch vụ Clare</p>
                    <h1 id="appointment-title">Cùng tìm một<br>khoảng sáng vừa đủ.</h1>
                </div>
                <p>Bạn chỉ cần cho Clare biết nhu cầu và thời gian phù hợp. Yêu cầu sẽ được nhân viên xem lại và xác nhận thủ công, chưa phải là một lịch hẹn đã chốt.</p>
            </div>

            <form class="appointment-form" action="{{ route('appointments.store') }}" method="POST" data-appointment-form>
                @csrf

                <section class="appointment-section" aria-labelledby="appointment-type-title">
                    <p class="eyebrow">01 / Nhu cầu</p>
                    <h2 id="appointment-type-title">Clare có thể giúp gì?</h2>

                    <div class="service-options">
                        <label class="service-option">
                            <input name="type" type="radio" value="consultation" @checked(old('type', $requestedType) === 'consultation')>
                            <span>
                                <strong>Tư vấn chọn đèn</strong>
                                <small>Trao đổi về căn phòng, nhu cầu sử dụng và lựa chọn ánh sáng phù hợp.</small>
                            </span>
                        </label>

                        <label class="service-option">
                            <input name="type" type="radio" value="installation" @checked(old('type', $requestedType) === 'installation')>
                            <span>
                                <strong>Yêu cầu lắp đặt</strong>
                                <small>Gửi thông tin để Clare ghi nhận nhu cầu hỗ trợ lắp đặt tại địa chỉ của bạn.</small>
                            </span>
                        </label>
                    </div>
                </section>

                <section class="appointment-section" aria-labelledby="appointment-contact-title">
                    <p class="eyebrow">02 / Liên hệ</p>
                    <h2 id="appointment-contact-title">Thông tin của bạn</h2>

                    <div class="checkout-form-grid">
                        <label class="checkout-field checkout-field-full">
                            <span>Họ và tên</span>
                            <input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required>
                        </label>

                        <label class="checkout-field">
                            <span>Email</span>
                            <input name="customer_email" type="email" value="{{ old('customer_email') }}" autocomplete="email" required>
                        </label>

                        <label class="checkout-field">
                            <span>Số điện thoại</span>
                            <input name="customer_phone" type="tel" value="{{ old('customer_phone') }}" autocomplete="tel" required>
                        </label>
                    </div>
                </section>

                <section class="appointment-section" aria-labelledby="appointment-time-title">
                    <p class="eyebrow">03 / Thời gian</p>
                    <h2 id="appointment-time-title">Khoảng thời gian bạn mong muốn</h2>
                    <p class="appointment-section-copy">Đây là thời gian đề xuất để Clare tham khảo, không phải lịch đã được xác nhận.</p>

                    <div class="checkout-form-grid">
                        <label class="checkout-field">
                            <span>Bắt đầu mong muốn</span>
                            <input name="preferred_starts_at" type="datetime-local" value="{{ old('preferred_starts_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}" required>
                        </label>

                        <label class="checkout-field">
                            <span>Kết thúc mong muốn <small>Không bắt buộc</small></span>
                            <input name="preferred_ends_at" type="datetime-local" value="{{ old('preferred_ends_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}">
                        </label>
                    </div>
                </section>

                <section class="appointment-section" aria-labelledby="appointment-address-title" data-appointment-address-section>
                    <p class="eyebrow">04 / Địa chỉ</p>
                    <h2 id="appointment-address-title">Không gian cần hỗ trợ</h2>
                    <p class="appointment-section-copy" data-appointment-address-copy>Không bắt buộc nếu bạn muốn tư vấn online. Các trường này cần có khi gửi yêu cầu lắp đặt.</p>

                    <div class="checkout-form-grid">
                        <label class="checkout-field checkout-field-full">
                            <span>Địa chỉ</span>
                            <input name="address_line_1" value="{{ old('address_line_1') }}" autocomplete="street-address" placeholder="Số nhà, tên đường" data-appointment-address-field>
                        </label>

                        <label class="checkout-field">
                            <span>Phường / Xã</span>
                            <input name="ward" value="{{ old('ward') }}" data-appointment-address-field>
                        </label>

                        <label class="checkout-field">
                            <span>Quận / Huyện</span>
                            <input name="district" value="{{ old('district') }}" data-appointment-address-field>
                        </label>

                        <label class="checkout-field">
                            <span>Tỉnh / Thành phố</span>
                            <input name="city" value="{{ old('city') }}" autocomplete="address-level1" data-appointment-address-field>
                        </label>

                        <label class="checkout-field">
                            <span>Địa chỉ bổ sung <small>Không bắt buộc</small></span>
                            <input name="address_line_2" value="{{ old('address_line_2') }}" autocomplete="address-line2">
                        </label>
                    </div>

                    <input name="country_code" type="hidden" value="VN">
                </section>

                <section class="appointment-section" aria-labelledby="appointment-note-title">
                    <p class="eyebrow">05 / Ghi chú</p>
                    <h2 id="appointment-note-title">Điều bạn muốn Clare biết</h2>

                    <label class="checkout-field checkout-note-field">
                        <span>Ghi chú <small>Không bắt buộc</small></span>
                        <textarea name="customer_note" rows="5" maxlength="2000" placeholder="Ví dụ: kích thước phòng, mẫu đèn đang cân nhắc hoặc lưu ý khi liên hệ.">{{ old('customer_note') }}</textarea>
                    </label>
                </section>

                <div class="appointment-submit">
                    <button class="button button-primary" type="submit">Gửi yêu cầu</button>
                    <p>Clare chỉ dùng thông tin này để xử lý yêu cầu tư vấn hoặc lắp đặt của bạn.</p>
                </div>
            </form>
        </div>
    </section>
@endsection
