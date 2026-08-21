@extends('layouts.admin', ['title' => 'Thuộc tính sản phẩm'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-attributes-title">
        <div class="admin-page-heading">
            <div><p class="admin-eyebrow">Catalog / Bộ lọc</p><h1 id="admin-attributes-title">Thuộc tính.</h1></div>
            <p>Công suất, màu ánh sáng, chất liệu và thông số kỹ thuật được quản lý động tại đây, không hard-code trong storefront.</p>
        </div>

        <div class="admin-promotions-toolbar"><p>{{ $attributes->count() }} thuộc tính đang lưu</p><a class="admin-primary-link" href="{{ route('admin.catalog.attributes.create') }}">Tạo thuộc tính</a></div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Thuộc tính</th><th>Kiểu lọc</th><th>Giá trị</th><th>Dùng làm bộ lọc</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($attributes as $attribute)
                        <tr>
                            <td><strong>{{ $attribute->name }}</strong><span>{{ $attribute->slug }}{{ $attribute->unit ? ' · '.$attribute->unit : '' }}</span></td>
                            <td>{{ ['select' => 'Danh sách', 'color' => 'Màu sắc', 'number' => 'Số', 'text' => 'Văn bản'][$attribute->filter_type] ?? $attribute->filter_type }}</td>
                            <td><strong>{{ $attribute->values_count }}</strong><span>{{ $attribute->values->take(3)->pluck('label')->join(', ') }}</span></td>
                            <td>{{ $attribute->is_filterable ? 'Có' : 'Chỉ hiển thị thông số' }}</td>
                            <td><span class="admin-status {{ $attribute->is_active ? 'admin-status-completed' : 'admin-status-cancelled' }}">{{ $attribute->is_active ? 'Đang bật' : 'Đã tắt' }}</span></td>
                            <td><a class="admin-record-link" href="{{ route('admin.catalog.attributes.edit', $attribute) }}">Quản lý giá trị</a></td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="6">Chưa có thuộc tính nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
