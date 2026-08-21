@extends('layouts.admin', ['title' => $category->exists ? 'Chỉnh sửa '.$category->name : 'Tạo danh mục'])

@php
    $isEditing = $category->exists;
    $action = $isEditing ? route('admin.catalog.categories.update', $category) : route('admin.catalog.categories.store');
@endphp

@section('content')
    <section class="admin-page" aria-labelledby="admin-category-form-title">
        <a class="admin-back-link" href="{{ route('admin.catalog.categories.index') }}">Trở về danh mục</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Catalog</p><h1 id="admin-category-form-title">{{ $isEditing ? 'Chỉnh sửa danh mục.' : 'Tạo danh mục.' }}</h1></div><p>Ảnh có thể là JPG, PNG hoặc WebP tối đa 5 MB. Khi thay ảnh, ảnh upload cũ sẽ được dọn an toàn.</p></div>

        <form class="admin-promotion-form" method="POST" action="{{ $action }}" enctype="multipart/form-data">
            @csrf
            @if ($isEditing) @method('PATCH') @endif
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Thông tin</p><h2>Nhóm sản phẩm</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Tên danh mục</span><input name="name" value="{{ old('name', $category->name) }}" required></label>
                    <label><span>Slug <small>Để trống để tự tạo từ tên</small></span><input name="slug" value="{{ old('slug', $category->slug) }}" placeholder="den-de-ban"></label>
                    <label>
                        <span>Danh mục cha <small>Để trống nếu là nhóm gốc</small></span>
                        <select name="parent_id">
                            <option value="">Danh mục gốc</option>
                            @foreach ($parentCategories as $parentCategory)
                                <option value="{{ $parentCategory->getKey() }}" @selected((string) old('parent_id', $category->parent_id) === (string) $parentCategory->getKey())>
                                    {{ str_repeat('— ', (int) $parentCategory->tree_depth) }}{{ $parentCategory->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="admin-form-full"><span>Mô tả</span><textarea name="description" rows="5" maxlength="5000">{{ old('description', $category->description) }}</textarea></label>
                    <label><span>SEO title <small>Tối đa 255 ký tự</small></span><input name="seo_title" maxlength="255" value="{{ old('seo_title', $category->seo_title) }}"></label>
                    <label><span>SEO description <small>Tối đa 500 ký tự</small></span><textarea name="seo_description" rows="3" maxlength="500">{{ old('seo_description', $category->seo_description) }}</textarea></label>
                    <label><span>Thứ tự hiển thị</span><input name="sort_order" type="number" min="0" value="{{ old('sort_order', $category->exists ? $category->sort_order : 0) }}" required></label>
                    <label><span>Ảnh danh mục <small>Không bắt buộc</small></span><input name="category_image" type="file" accept="image/jpeg,image/png,image/webp">@if($category->image_path)<small>Ảnh hiện tại: {{ $category->image_path }}</small>@endif</label>
                    <label><span>Hiển thị</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $category->exists ? $category->is_active : true))> Hiển thị danh mục tại cửa hàng</span></label>
                </div>
            </section>
            <button class="admin-form-submit" type="submit">{{ $isEditing ? 'Lưu danh mục' : 'Tạo danh mục' }}</button>
        </form>

        @if ($isEditing)
            <form class="admin-danger-zone" method="POST" action="{{ route('admin.catalog.categories.destroy', $category) }}">
                @csrf @method('DELETE')
                <div><strong>Lưu trữ danh mục</strong><p>Chỉ thực hiện được khi danh mục không còn sản phẩm. Dữ liệu được soft-delete, không bị xóa vĩnh viễn.</p></div>
                <button type="submit">Lưu trữ</button>
            </form>
        @endif
    </section>
@endsection
