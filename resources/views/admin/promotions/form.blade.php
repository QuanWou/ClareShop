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
            <p>Ưu đãi chỉ giảm tiền hàng. Tổng đơn vẫn được tính lại trong transaction checkout, không nhận giá trị từ form khách hàng.</p>
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
                    <label><span>Kiểu giảm</span><select name="discount_type" required><option value="percentage" @selected(old('discount_type', $promotion->discount_type ?: 'percentage') === 'percentage')>Phần trăm</option><option value="fixed" @selected(old('discount_type', $promotion->discount_type) === 'fixed')>Số tiền cố định</option></select></label>
                    <label><span>Mức giảm</span><input name="discount_value" type="number" min="1" step="1" value="{{ old('discount_value', $promotion->discount_value) }}" required><small>Phần trăm tối đa 100; số tiền tính bằng VND.</small></label>
                    <label><span>Đơn tối thiểu <small>Không bắt buộc</small></span><input name="minimum_order_amount" type="number" min="0" step="1" value="{{ old('minimum_order_amount', $promotion->minimum_order_amount) }}" placeholder="500000"></label>
                    <label><span>Giảm tối đa <small>Không bắt buộc</small></span><input name="maximum_discount_amount" type="number" min="1" step="1" value="{{ old('maximum_discount_amount', $promotion->maximum_discount_amount) }}" placeholder="300000"></label>
                    <label><span>Giới hạn lượt dùng <small>Không bắt buộc</small></span><input name="usage_limit" type="number" min="1" step="1" value="{{ old('usage_limit', $promotion->usage_limit) }}" placeholder="100"></label>
                    <label><span>Trạng thái</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $promotion->exists ? $promotion->is_active : true))> Đang bật cho checkout</span></label>
                    <label><span>Bắt đầu <small>Không bắt buộc</small></span><input name="starts_at" type="datetime-local" value="{{ old('starts_at', $promotion->starts_at?->format('Y-m-d\\TH:i')) }}"></label>
                    <label><span>Kết thúc <small>Không bắt buộc</small></span><input name="ends_at" type="datetime-local" value="{{ old('ends_at', $promotion->ends_at?->format('Y-m-d\\TH:i')) }}"></label>
                </div>
            </section>

            @if ($isEditing)
                <p class="admin-promotion-audit">Đã dùng {{ $promotion->usage_count }} lần. Sửa mã không thay đổi snapshot ưu đãi của các đơn đã đặt.</p>
            @endif

            <button class="admin-form-submit" type="submit">{{ $isEditing ? 'Lưu mã ưu đãi' : 'Tạo mã ưu đãi' }}</button>
        </form>
    </section>
@endsection
