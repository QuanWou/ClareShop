@extends('layouts.admin', ['title' => 'Thương hiệu'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-brands-title">
        <div class="admin-page-heading">
            <div><p class="admin-eyebrow">Catalog</p><h1 id="admin-brands-title">Thương hiệu.</h1></div>
            <p>Quản lý tên, logo và nguồn gốc thương hiệu để khách có thể lọc sản phẩm chính xác.</p>
        </div>

        <div class="admin-promotions-toolbar"><p>{{ $brands->count() }} thương hiệu đang lưu</p><a class="admin-primary-link" href="{{ route('admin.catalog.brands.create') }}">Tạo thương hiệu</a></div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Thương hiệu</th><th>Quốc gia</th><th>Sản phẩm</th><th>Thứ tự</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td><strong>{{ $brand->name }}</strong><span>{{ $brand->slug }}</span></td>
                            <td>{{ $brand->country ?: 'Chưa cập nhật' }}</td>
                            <td>{{ $brand->products_count }}</td>
                            <td>{{ $brand->sort_order }}</td>
                            <td><span class="admin-status {{ $brand->is_active ? 'admin-status-completed' : 'admin-status-cancelled' }}">{{ $brand->is_active ? 'Đang bật' : 'Đã tắt' }}</span></td>
                            <td><a class="admin-record-link" href="{{ route('admin.catalog.brands.edit', $brand) }}">Chỉnh sửa</a></td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="6">Chưa có thương hiệu nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
