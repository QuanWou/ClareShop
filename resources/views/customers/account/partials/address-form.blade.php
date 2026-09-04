@php($user = $user ?? auth()->user())
<form class="account-form account-address-form" action="{{ $formAction }}" method="POST">
    @csrf
    @if ($formMethod !== 'POST') @method($formMethod) @endif
    <div class="account-form-grid">
        <label class="account-field"><span>Nhãn địa chỉ</span><input name="label" value="{{ old('label', $address?->label ?? 'Nhà riêng') }}" maxlength="60" placeholder="Nhà riêng, Văn phòng"></label>
        <label class="account-field"><span>Tên người nhận</span><input name="recipient_name" value="{{ old('recipient_name', $address?->recipient_name ?? $user->name) }}" autocomplete="shipping name" maxlength="255" required></label>
        <label class="account-field"><span>Số điện thoại</span><input name="address_phone" type="tel" value="{{ old('address_phone', $address?->phone ?? $user->phone) }}" autocomplete="shipping tel" maxlength="30" required></label>
        <label class="account-field"><span>Tỉnh / Thành phố</span><input name="city" value="{{ old('city', $address?->city) }}" autocomplete="shipping address-level1" maxlength="255" required></label>
        <label class="account-field account-field-full"><span>Địa chỉ</span><input name="address_line_1" value="{{ old('address_line_1', $address?->address_line_1) }}" autocomplete="shipping street-address" maxlength="255" placeholder="Số nhà, tên đường" required></label>
        <label class="account-field"><span>Phường / Xã</span><input name="ward" value="{{ old('ward', $address?->ward) }}" maxlength="255" required></label>
        <label class="account-field"><span>Quận / Huyện</span><input name="district" value="{{ old('district', $address?->district) }}" maxlength="255" required></label>
        <label class="account-field"><span>Mã bưu chính <small>Không bắt buộc</small></span><input name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}" maxlength="20"></label>
        <label class="account-field"><span>Địa chỉ bổ sung <small>Không bắt buộc</small></span><input name="address_line_2" value="{{ old('address_line_2', $address?->address_line_2) }}" maxlength="255"></label>
        <label class="account-address-default-check"><input name="is_default" type="checkbox" value="1" @checked(old('is_default', $address?->is_default ?? false))><span>Đặt làm địa chỉ mặc định</span></label>
    </div>
    <input name="country_code" type="hidden" value="VN">
    <button class="button button-primary" type="submit">{{ $submitLabel }}</button>
</form>
