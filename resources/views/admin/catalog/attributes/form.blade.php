@extends('layouts.admin', ['title' => $attribute->exists ? 'Thuộc tính '.$attribute->name : 'Tạo thuộc tính'])

@php
    $isEditing = $attribute->exists;
    $action = $isEditing ? route('admin.catalog.attributes.update', $attribute) : route('admin.catalog.attributes.store');
@endphp

@section('content')
    <section class="admin-page" aria-labelledby="admin-attribute-form-title">
        <a class="admin-back-link" href="{{ route('admin.catalog.attributes.index') }}">Trở về thuộc tính</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Catalog / Bộ lọc</p><h1 id="admin-attribute-form-title">{{ $isEditing ? $attribute->name : 'Tạo thuộc tính.' }}</h1></div><p>Giá trị được gắn vào sản phẩm và tự động xuất hiện trong bộ lọc khi thuộc tính đang bật.</p></div>

        <form class="admin-promotion-form" method="POST" action="{{ $action }}">
            @csrf
            @if ($isEditing) @method('PATCH') @endif
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Cấu hình</p><h2>Thuộc tính sản phẩm</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Tên thuộc tính</span><input name="name" value="{{ old('name', $attribute->name) }}" required></label>
                    <label><span>Slug <small>Để trống để tự tạo</small></span><input name="slug" value="{{ old('slug', $attribute->slug) }}"></label>
                    <label><span>Đơn vị <small>Ví dụ W, K, V</small></span><input name="unit" value="{{ old('unit', $attribute->unit) }}" maxlength="30"></label>
                    <label><span>Kiểu hiển thị</span><select name="filter_type" required><option value="select" @selected(old('filter_type', $attribute->filter_type ?: 'select') === 'select')>Danh sách chọn</option><option value="color" @selected(old('filter_type', $attribute->filter_type) === 'color')>Mẫu màu</option><option value="number" @selected(old('filter_type', $attribute->filter_type) === 'number')>Giá trị số</option><option value="text" @selected(old('filter_type', $attribute->filter_type) === 'text')>Văn bản</option></select></label>
                    <label><span>Thứ tự</span><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $attribute->exists ? $attribute->sort_order : 0) }}" required></label>
                    <label><span>Bộ lọc</span><input name="is_filterable" type="hidden" value="0"><span class="admin-checkbox"><input name="is_filterable" type="checkbox" value="1" @checked(old('is_filterable', $attribute->exists ? $attribute->is_filterable : true))> Cho khách lọc theo thuộc tính này</span></label>
                    <label><span>Trạng thái</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $attribute->exists ? $attribute->is_active : true))> Đang sử dụng</span></label>
                </div>
            </section>
            <button class="admin-form-submit" type="submit">{{ $isEditing ? 'Lưu thuộc tính' : 'Tạo thuộc tính' }}</button>
        </form>

        @if ($isEditing)
            <section class="admin-catalog-section" aria-labelledby="admin-attribute-values-title">
                <div class="admin-section-heading"><div><p class="admin-eyebrow">Giá trị có thể chọn</p><h2 id="admin-attribute-values-title">{{ $attribute->values->count() }} giá trị</h2></div><p>Một sản phẩm có thể nhận một hoặc nhiều giá trị tùy thuộc thuộc tính.</p></div>

                <div class="admin-variant-stack">
                    @foreach ($attribute->values as $value)
                        <article class="admin-variant-card">
                            <form method="POST" action="{{ route('admin.catalog.attributes.values.update', [$attribute, $value]) }}">
                                @csrf
                                @method('PATCH')
                                <div class="admin-form-grid">
                                    <label><span>Nhãn</span><input name="label" value="{{ $value->label }}" required></label>
                                    <label><span>Slug</span><input name="slug" value="{{ $value->slug }}"></label>
                                    <label><span>Giá trị số</span><input name="numeric_value" type="number" step="0.01" value="{{ $value->numeric_value }}"></label>
                                    <label><span>Mã màu</span><input name="color_hex" value="{{ $value->color_hex }}" placeholder="#F4E7C5"></label>
                                    <label><span>Thứ tự</span><input name="sort_order" type="number" min="0" value="{{ $value->sort_order }}" required></label>
                                </div>
                                <p class="admin-promotion-audit">Đang dùng cho {{ $value->products_count }} sản phẩm.</p>
                                <button class="admin-form-submit" type="submit">Lưu giá trị</button>
                            </form>
                            <form class="admin-inline-delete" method="POST" action="{{ route('admin.catalog.attributes.values.destroy', [$attribute, $value]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit">Xóa giá trị</button>
                            </form>
                        </article>
                    @endforeach
                </div>

                <form class="admin-panel admin-add-variant-form" method="POST" action="{{ route('admin.catalog.attributes.values.store', $attribute) }}">
                    @csrf
                    <div class="admin-panel-heading"><div><p class="admin-eyebrow">Bổ sung</p><h2>Thêm giá trị</h2></div></div>
                    <div class="admin-form-grid">
                        <label><span>Nhãn</span><input name="label" required placeholder="Ví dụ: Vàng ấm"></label>
                        <label><span>Slug <small>Để trống để tự tạo</small></span><input name="slug"></label>
                        <label><span>Giá trị số <small>Nếu có</small></span><input name="numeric_value" type="number" step="0.01"></label>
                        <label><span>Mã màu <small>Nếu có</small></span><input name="color_hex" placeholder="#F4E7C5"></label>
                        <label><span>Thứ tự</span><input name="sort_order" type="number" min="0" value="{{ ($attribute->values->max('sort_order') ?? 0) + 10 }}" required></label>
                    </div>
                    <button class="admin-form-submit" type="submit">Thêm giá trị</button>
                </form>
            </section>
        @endif
    </section>
@endsection
