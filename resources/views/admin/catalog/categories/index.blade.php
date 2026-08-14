@extends('layouts.admin', ['title' => 'Danh mục'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-categories-title">
        <div class="admin-page-heading">
            <div><p class="admin-eyebrow">Catalog</p><h1 id="admin-categories-title">Danh mục</h1></div>
            <p>Nhóm sản phẩm quyết định cách khách khám phá catalog. Chỉ danh mục đang bật mới xuất hiện ở storefront.</p>
        </div>

        <div class="admin-promotions-toolbar"><p>{{ $categories->count() }} danh mục đang lưu</p><a class="admin-primary-link" href="{{ route('admin.catalog.categories.create') }}">Tạo danh mục</a></div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Danh mục</th><th>Slug</th><th>Sản phẩm</th><th>Thứ tự</th><th>Hiển thị</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->name }}</strong><span>{{ \Illuminate\Support\Str::limit($category->description, 70) }}</span></td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->products_count }}</td>
                            <td>{{ $category->sort_order }}</td>
                            <td><span class="admin-status {{ $category->is_active ? 'admin-status-completed' : 'admin-status-cancelled' }}">{{ $category->is_active ? 'Đang bật' : 'Đã tắt' }}</span></td>
                            <td><a class="admin-record-link" href="{{ route('admin.catalog.categories.edit', $category) }}">Chỉnh sửa</a></td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="6">Chưa có danh mục nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
