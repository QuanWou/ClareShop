@extends('layouts.admin', ['title' => $promotion->exists ? 'Chỉnh sửa '.$promotion->code : 'Tạo mã ưu đãi'])

@php
    $isEditing = $promotion->exists;
    $formAction = $isEditing ? route('admin.promotions.update', $promotion) : route('admin.promotions.store');
@endphp

@section('content')
    <section class="admin-page" aria-labelledby="admin-promotion-form-title">
        <a class="admin-back-link" href="{{ route('admin.promotions.index') }}">Trở về danh sách mã</a>

        <div class="admin-page-heading">
            <div>
                <p class="admin-eyebrow">Khuyến mãi</p>
                <h1 id="admin-promotion-form-title">{{ $isEditing ? 'Chỉnh sửa mã.' : 'Tạo mã mới.' }}</h1>
            </div>
            <p>Ưu đãi chỉ giảm tiền hàng. Voucher công khai có thể được khách nhận vào tài khoản; lượt dùng chỉ tăng sau khi thanh toán được xác nhận.</p>
        </div>

        <form class="admin-promotion-form" method="POST" action="{{ $formAction }}">
            @csrf
            @if ($isEditing)
                @method('PATCH')
            @endif

            <section class="admin-panel">
                <div class="admin-panel-heading">
                    <div><p class="admin-eyebrow">Cấu hình</p><h2>Thông tin mã</h2></div>
                </div>

                <div class="admin-form-grid">
                    <label><span>Mã ưu đãi</span><input name="code" value="{{ old('code', $promotion->code) }}" maxlength="50" placeholder="CLARE10" required></label>
                    <label><span>Tên chương trình</span><input name="name" value="{{ old('name', $promotion->name) }}" maxlength="150" required></label>
                    <label class="admin-form-field-full"><span>Mô tả ngắn <small>Không bắt buộc</small></span><textarea name="description" rows="3" maxlength="2000" placeholder="Điều kiện, phạm vi và thông điệp ngắn của ưu đãi.">{{ old('description', $promotion->description) }}</textarea></label>
                    <label class="admin-form-field-full"><span>Ảnh/banner <small>Không bắt buộc</small></span><input name="banner_path" value="{{ old('banner_path', $promotion->banner_path) }}" maxlength="255" placeholder="Đường dẫn ảnh đã tải trong Thư viện media"></label>
                    <label><span>Kiểu giảm</span><select name="discount_type" required><option value="percentage" @selected(old('discount_type', $promotion->discount_type ?: 'percentage') === 'percentage')>Phần trăm</option><option value="fixed" @selected(old('discount_type', $promotion->discount_type) === 'fixed')>Số tiền cố định</option></select></label>
                    <label><span>Mức giảm</span><input name="discount_value" type="number" min="1" step="1" value="{{ old('discount_value', $promotion->discount_value) }}" required><small>Phần trăm tối đa 100; số tiền tính bằng VND.</small></label>
                    <label><span>Đơn tối thiểu <small>Không bắt buộc</small></span><input name="minimum_order_amount" type="number" min="0" step="1" value="{{ old('minimum_order_amount', $promotion->minimum_order_amount) }}" placeholder="500000"></label>
                    <label><span>Đơn tối đa <small>Không bắt buộc</small></span><input name="maximum_order_amount" type="number" min="0" step="1" value="{{ old('maximum_order_amount', $promotion->maximum_order_amount) }}" placeholder="2000000"></label>
                    <label><span>Giảm tối đa <small>Không bắt buộc</small></span><input name="maximum_discount_amount" type="number" min="1" step="1" value="{{ old('maximum_discount_amount', $promotion->maximum_discount_amount) }}" placeholder="300000"></label>
                    <label><span>Giới hạn lượt dùng <small>Không bắt buộc</small></span><input name="usage_limit" type="number" min="1" step="1" value="{{ old('usage_limit', $promotion->usage_limit) }}" placeholder="100"></label>
                    <label><span>Giới hạn lượt nhận <small>Không bắt buộc</small></span><input name="claim_limit" type="number" min="1" step="1" value="{{ old('claim_limit', $promotion->claim_limit) }}" placeholder="100"></label>
                    <label><span>Lượt dùng mỗi khách</span><input name="per_user_usage_limit" type="number" min="1" step="1" value="{{ old('per_user_usage_limit', $promotion->per_user_usage_limit ?: 1) }}" required></label>
                    <label><span>Trạng thái</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $promotion->exists ? $promotion->is_active : true))> Đang bật cho checkout</span></label>
                    <label><span>Hiển thị trong Kho voucher</span><input name="is_public" type="hidden" value="0"><span class="admin-checkbox"><input name="is_public" type="checkbox" value="1" @checked(old('is_public', $promotion->exists ? $promotion->is_public : true))> Cho khách xem và nhận</span></label>
                    <label><span>Điều kiện dùng</span><input name="requires_claim" type="hidden" value="0"><span class="admin-checkbox"><input name="requires_claim" type="checkbox" value="1" @checked(old('requires_claim', $promotion->exists ? $promotion->requires_claim : true))> Phải nhận vào tài khoản trước</span></label>
                    <input name="application_scope" type="hidden" value="order">
                    <label><span>Bắt đầu <small>Không bắt buộc</small></span><input name="starts_at" type="datetime-local" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\\TH:i')) }}"></label>
                    <label><span>Kết thúc <small>Không bắt buộc</small></span><input name="ends_at" type="datetime-local" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d\\TH:i')) }}"></label>
                </div>
            </section>

            @if ($isEditing)
                <p class="admin-promotion-audit">Đã nhận {{ $promotion->claim_count }} lần, đã dùng {{ $promotion->usage_count }} lần. Sửa mã không thay đổi snapshot ưu đãi của các đơn đã đặt.</p>
            @endif

            <button class="admin-form-submit" type="submit">{{ $isEditing ? 'Lưu mã ưu đãi' : 'Tạo mã ưu đãi' }}</button>
        </form>
    </section>
@endsection
