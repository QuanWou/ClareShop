@extends('layouts.admin', ['title' => 'Sản phẩm'])

@section('content')
    <section class="admin-page" aria-labelledby="admin-products-title">
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Catalog</p><h1 id="admin-products-title">Sản phẩm.</h1></div><p>Tìm theo tên, slug hoặc SKU; kiểm soát trạng thái, danh mục, biến thể, giá, tồn kho và ảnh của từng mẫu đèn.</p></div>

        <div class="admin-promotions-toolbar"><p>{{ $products->total() }} sản phẩm phù hợp</p><a class="admin-primary-link" href="{{ route('admin.catalog.products.create') }}">Tạo sản phẩm</a></div>

        <form class="admin-filters" method="GET" action="{{ route('admin.catalog.products.index') }}">
            <label><span>Tìm kiếm</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Tên sản phẩm, slug hoặc SKU"></label>
            <label><span>Danh mục</span><select name="category_id"><option value="">Tất cả danh mục</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label><span>Trạng thái</span><select name="status"><option value="">Tất cả</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Đang bật</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Đã tắt</option><option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Đã lưu trữ</option></select></label>
            <button type="submit">Lọc</button>
        </form>

        <div class="admin-table-wrap">
            <table class="admin-table"><thead><tr><th>Sản phẩm</th><th>Danh mục</th><th>Biến thể / ảnh</th><th>Giá từ</th><th>Xuất bản</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td><strong>{{ $product->name }}</strong><span>{{ $product->slug }}</span></td>
                            <td>{{ $product->category?->name ?? 'Chưa phân loại' }}</td>
                            <td>{{ $product->variants_count }} biến thể<span>{{ $product->images_count }} ảnh</span></td>
                            <td>{{ $product->minimum_price ? \App\Modules\Shared\Support\Money::formatVnd($product->minimum_price) : 'Chưa có' }}</td>
                            <td>{{ $product->published_at?->format('d/m/Y H:i') ?? 'Chưa xuất bản' }}</td>
                            <td><span class="admin-status {{ $product->trashed() || ! $product->is_active ? 'admin-status-cancelled' : 'admin-status-completed' }}">{{ $product->trashed() ? 'Đã lưu trữ' : ($product->is_active ? 'Đang bật' : 'Đã tắt') }}</span></td>
                            <td>
                                @if ($product->trashed())
                                    <form class="admin-restore-form" method="POST" action="{{ route('admin.catalog.products.restore', $product) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="admin-restore-button" type="submit">Khôi phục</button>
                                    </form>
                                @else
                                    <a class="admin-record-link" href="{{ route('admin.catalog.products.edit', $product) }}">Quản lý</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="admin-empty-cell" colspan="7">Không tìm thấy sản phẩm phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())<div class="admin-pagination">{{ $products->links() }}</div>@endif
    </section>
@endsection
