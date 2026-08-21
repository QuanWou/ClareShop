@extends('layouts.admin', ['title' => $brand->exists ? 'Chỉnh sửa '.$brand->name : 'Tạo thương hiệu'])

@php
    $isEditing = $brand->exists;
    $action = $isEditing ? route('admin.catalog.brands.update', $brand) : route('admin.catalog.brands.store');
@endphp

@section('content')
    <section class="admin-page" aria-labelledby="admin-brand-form-title">
        <a class="admin-back-link" href="{{ route('admin.catalog.brands.index') }}">Trở về thương hiệu</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Catalog</p><h1 id="admin-brand-form-title">{{ $isEditing ? 'Chỉnh sửa thương hiệu.' : 'Tạo thương hiệu.' }}</h1></div><p>Thông tin này xuất hiện trong bộ lọc và thông số sản phẩm.</p></div>

        <form class="admin-promotion-form" method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @if ($isEditing) @method('PATCH') @endif
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Nhận diện</p><h2>Thông tin thương hiệu</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Tên thương hiệu</span><input name="name" value="{{ old('name', $brand->name) }}" required></label>
                    <label><span>Slug <small>Để trống để tự tạo</small></span><input name="slug" value="{{ old('slug', $brand->slug) }}"></label>
                    <label><span>Quốc gia</span><input name="country" value="{{ old('country', $brand->country) }}"></label>
                    <label><span>Thứ tự hiển thị</span><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $brand->exists ? $brand->sort_order : 0) }}" required></label>
                    <label class="admin-form-full"><span>Mô tả</span><textarea name="description" rows="5" maxlength="5000">{{ old('description', $brand->description) }}</textarea></label>
                    <label><span>Logo <small>JPG, PNG hoặc WebP</small></span><input name="brand_logo" type="file" accept="image/jpeg,image/png,image/webp">@if($brand->logo_path)<small>Hiện tại: {{ $brand->logo_path }}</small>@endif</label>
                    <label><span>Trạng thái</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $brand->exists ? $brand->is_active : true))> Cho phép hiển thị trong catalog</span></label>
                </div>
            </section>
            <button class="admin-form-submit" type="submit">{{ $isEditing ? 'Lưu thương hiệu' : 'Tạo thương hiệu' }}</button>
        </form>

        @if ($isEditing)
            <form class="admin-danger-zone" method="POST" action="{{ route('admin.catalog.brands.destroy', $brand) }}">
                @csrf
                @method('DELETE')
                <div><strong>Lưu trữ thương hiệu</strong><p>Sản phẩm vẫn được giữ nguyên; thương hiệu sẽ ngừng xuất hiện trong bộ lọc storefront.</p></div>
                <button type="submit">Lưu trữ</button>
            </form>
        @endif
    </section>
@endsection
