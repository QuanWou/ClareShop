@extends('layouts.admin', ['title' => 'Tạo sản phẩm'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-product-create-title">
        <a class="admin-back-link" href="{{ route('admin.catalog.products.index') }}">Trở về sản phẩm</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Catalog</p><h1 id="admin-product-create-title">Tạo sản phẩm.</h1></div><p>Một sản phẩm mới cần có tối thiểu một biến thể bán được. Giá, SKU, tồn kho và trọng lượng luôn thuộc về biến thể.</p></div>

        <form class="admin-promotion-form" method="POST" action="{{ route('admin.catalog.products.store') }}">
            @csrf
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Mẫu đèn</p><h2>Thông tin sản phẩm</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Tên sản phẩm</span><input name="name" value="{{ old('name') }}" required></label>
                    <label><span>Slug <small>Để trống để tự tạo</small></span><input name="slug" value="{{ old('slug') }}"></label>
                    @include('admin.catalog.products.partials.taxonomy-fields', ['product' => null])
                    <label><span>Xuất bản lúc <small>Để trống để lưu nháp</small></span><input name="published_at" type="datetime-local" value="{{ old('published_at') }}"></label>
                    <label><span>Chất liệu <small>Không bắt buộc</small></span><input name="material" value="{{ old('material') }}"></label>
                    <label><span>Kích thước <small>Không bắt buộc</small></span><input name="dimensions" value="{{ old('dimensions') }}"></label>
                    <label class="admin-form-full"><span>Mô tả ngắn</span><input name="short_description" maxlength="500" value="{{ old('short_description') }}"></label>
                    <label class="admin-form-full"><span>Mô tả chi tiết</span><textarea name="description" rows="6" maxlength="10000">{{ old('description') }}</textarea></label>
                    <label><span>Trạng thái</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', true))> Cho phép bán sản phẩm</span></label>
                    <label><span>Nổi bật</span><input name="is_featured" type="hidden" value="0"><span class="admin-checkbox"><input name="is_featured" type="checkbox" value="1" @checked(old('is_featured', false))> Đưa vào nhóm nổi bật</span></label>
                </div>
            </section>

            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Biến thể đầu tiên</p><h2>Giá &amp; tồn kho</h2></div></div>
                @include('admin.catalog.products.partials.variant-fields', ['prefix' => 'initial_variant', 'variant' => null])
            </section>
            <button class="admin-form-submit" type="submit">Tạo sản phẩm</button>
        </form>
    </section>
@endsection
