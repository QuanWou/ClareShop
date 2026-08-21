@extends('layouts.admin', ['title' => 'Danh mục'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-categories-title">
        <div class="admin-page-heading">
            <div><p class="admin-eyebrow">Catalog</p><h1 id="admin-categories-title">Danh mục</h1></div>
            <p>Danh mục được hiển thị dạng cây. Danh mục đang bật sẽ xuất hiện ở điều hướng và bộ lọc storefront, kể cả khi vừa được Admin tạo.</p>
        </div>

        <div class="admin-promotions-toolbar"><p>{{ $categories->count() }} danh mục đang lưu</p><a class="admin-primary-link" href="{{ route('admin.catalog.categories.create') }}">Tạo danh mục</a></div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Danh mục</th><th>Danh mục cha</th><th>Slug</th><th>Sản phẩm</th><th>Thứ tự</th><th>Hiển thị</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <div class="admin-category-tree-name" style="--category-depth: {{ $category->tree_depth }}">
                                    @if ($category->tree_depth > 0)<span aria-hidden="true">↳</span>@endif
                                    <div><strong>{{ $category->name }}</strong><span>{{ \Illuminate\Support\Str::limit($category->description, 70) }}</span></div>
                                </div>
                            </td>
                            <td>{{ $category->parent?->name ?? 'Danh mục gốc' }}</td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->products_count }}<span>{{ $category->children_count }} danh mục con</span></td>
                            <td>{{ $category->sort_order }}</td>
                            <td><span class="admin-status {{ $category->is_active ? 'admin-status-completed' : 'admin-status-cancelled' }}">{{ $category->is_active ? 'Đang bật' : 'Đã tắt' }}</span></td>
                            <td><a class="admin-record-link" href="{{ route('admin.catalog.categories.edit', $category) }}">Chỉnh sửa</a></td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="7">Chưa có danh mục nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
