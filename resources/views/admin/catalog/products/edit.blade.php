@extends('layouts.admin', ['title' => 'Quản lý '.$product->name])

@section('content')
    @php
        $activeVariants = $product->variants->where('is_active', true);
        $totalStock = (int) $product->variants->sum('stock_quantity');
        $minimumPrice = $product->variants->min('price');
        $maximumPrice = $product->variants->max('price');
    @endphp
    <section class="admin-page" aria-labelledby="admin-product-edit-title">
        <a class="admin-back-link" href="{{ route('admin.catalog.products.index') }}">Trở về sản phẩm</a>
        <div class="admin-page-heading"><div><p class="admin-eyebrow">Catalog</p><h1 id="admin-product-edit-title">{{ $product->name }}</h1></div><p>{{ $product->slug }} · Cập nhật nội dung, biến thể, giá, tồn kho và ảnh tại một nơi.</p></div>

        <dl class="admin-product-kpis" aria-label="Tổng quan sản phẩm">
            <div><dt>Biến thể đang bán</dt><dd>{{ $activeVariants->count() }} / {{ $product->variants->count() }}</dd><small>{{ $product->variants->count() - $activeVariants->count() }} biến thể đang tắt</small></div>
            <div><dt>Tổng tồn kho</dt><dd>{{ number_format($totalStock, 0, ',', '.') }}</dd><small>{{ $totalStock > 0 ? 'Sản phẩm sẵn sàng bán' : 'Tất cả biến thể đã hết hàng' }}</small></div>
            <div><dt>Khoảng giá</dt><dd>{{ $minimumPrice ? \App\Modules\Shared\Support\Money::formatVnd($minimumPrice) : 'Chưa có' }}</dd><small>@if($maximumPrice && (int)$maximumPrice !== (int)$minimumPrice) Cao nhất {{ \App\Modules\Shared\Support\Money::formatVnd($maximumPrice) }} @else Giá đồng nhất @endif</small></div>
            <div><dt>Hình ảnh</dt><dd>{{ $product->images->count() }}</dd><small>{{ $product->images->whereNotNull('product_variant_id')->count() }} ảnh gắn biến thể</small></div>
        </dl>

        <form class="admin-promotion-form" method="POST" action="{{ route('admin.catalog.products.update', $product) }}">
            @csrf @method('PATCH')
            <section class="admin-panel">
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Mẫu đèn</p><h2>Thông tin sản phẩm</h2></div></div>
                <div class="admin-form-grid">
                    <label><span>Tên sản phẩm</span><input name="name" value="{{ old('name', $product->name) }}" required></label>
                    <label><span>Slug</span><input name="slug" value="{{ old('slug', $product->slug) }}"></label>
                    @include('admin.catalog.products.partials.taxonomy-fields', ['product' => $product])
                    <label><span>Xuất bản lúc <small>Để trống để lưu nháp</small></span><input name="published_at" type="datetime-local" value="{{ old('published_at', $product->published_at?->format('Y-m-d\\TH:i')) }}"></label>
                    <label><span>Chất liệu</span><input name="material" value="{{ old('material', $product->material) }}"></label>
                    <label><span>Kích thước</span><input name="dimensions" value="{{ old('dimensions', $product->dimensions) }}"></label>
                    <label class="admin-form-full"><span>Mô tả ngắn</span><input name="short_description" maxlength="500" value="{{ old('short_description', $product->short_description) }}"></label>
                    <label class="admin-form-full"><span>Mô tả chi tiết</span><textarea name="description" rows="6" maxlength="10000">{{ old('description', $product->description) }}</textarea></label>
                    <label><span>Trạng thái</span><input name="is_active" type="hidden" value="0"><span class="admin-checkbox"><input name="is_active" type="checkbox" value="1" @checked(old('is_active', $product->is_active))> Cho phép bán sản phẩm</span></label>
                    <label><span>Nổi bật</span><input name="is_featured" type="hidden" value="0"><span class="admin-checkbox"><input name="is_featured" type="checkbox" value="1" @checked(old('is_featured', $product->is_featured))> Đưa vào nhóm nổi bật</span></label>
                </div>
            </section>
            <button class="admin-form-submit" type="submit">Lưu thông tin sản phẩm</button>
        </form>

        <section class="admin-catalog-section" aria-labelledby="admin-variants-title">
            <div class="admin-section-heading"><div><p class="admin-eyebrow">Bán hàng</p><h2 id="admin-variants-title">Biến thể, giá &amp; tồn kho</h2></div><p>Mỗi màu có SKU, giá, tồn kho và trọng lượng riêng.</p></div>
            <div class="admin-variant-stack">
                @foreach ($product->variants as $variant)
                    <article class="admin-variant-card">
                        <form method="POST" action="{{ route('admin.catalog.products.variants.update', [$product, $variant]) }}">
                            @csrf @method('PATCH')
                            <div class="admin-variant-card-heading"><strong>{{ $variant->color_name }}</strong><span>{{ $variant->sku }}</span></div>
                            @include('admin.catalog.products.partials.variant-fields', ['prefix' => '', 'variant' => $variant])
                            <button class="admin-form-submit" type="submit">Lưu biến thể</button>
                        </form>
                        <form method="POST" action="{{ route('admin.catalog.products.variants.destroy', [$product, $variant]) }}" class="admin-inline-delete">@csrf @method('DELETE')<button type="submit">Lưu trữ biến thể</button></form>
                    </article>
                @endforeach
            </div>
            @if ($product->archivedVariants->isNotEmpty())
                <div class="admin-archived-variants">
                    <div class="admin-archived-variants-heading">
                        <strong>Biến thể đã lưu trữ</strong>
                        <span>{{ $product->archivedVariants->count() }} biến thể có thể khôi phục</span>
                    </div>
                    <div class="admin-variant-stack">
                        @foreach ($product->archivedVariants as $variant)
                            <article class="admin-variant-card admin-variant-card-archived">
                                <div>
                                    <div class="admin-variant-card-heading"><strong>{{ $variant->color_name }}</strong><span>{{ $variant->sku }}</span></div>
                                    <p class="admin-empty">Đã lưu trữ {{ $variant->deleted_at?->format('d/m/Y H:i') }} · Giá, tồn kho và trạng thái cũ vẫn được giữ nguyên.</p>
                                </div>
                                <form class="admin-inline-restore" method="POST" action="{{ route('admin.catalog.products.variants.restore', [$product, $variant]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit">Khôi phục biến thể</button>
                                </form>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endif
            <form class="admin-panel admin-add-variant-form" method="POST" action="{{ route('admin.catalog.products.variants.store', $product) }}">
                @csrf
                <div class="admin-panel-heading"><div><p class="admin-eyebrow">Thêm màu</p><h2>Biến thể mới</h2></div></div>
                @include('admin.catalog.products.partials.variant-fields', ['prefix' => '', 'variant' => null])
                <button class="admin-form-submit" type="submit">Thêm biến thể</button>
            </form>
        </section>

        <section class="admin-catalog-section" aria-labelledby="admin-images-title">
            <div class="admin-section-heading"><div><p class="admin-eyebrow">Hình ảnh</p><h2 id="admin-images-title">Ảnh sản phẩm</h2></div><p>Ảnh đầu tiên theo thứ tự sẽ là ảnh ưu tiên ở storefront.</p></div>
            <form class="admin-panel admin-image-upload-form" method="POST" action="{{ route('admin.catalog.products.images.store', $product) }}" enctype="multipart/form-data">
                @csrf
                <div class="admin-form-grid">
                    <label><span>Tệp ảnh <small>JPG, PNG hoặc WebP · tối đa 5 MB</small></span><input name="image" type="file" accept="image/jpeg,image/png,image/webp" required></label>
                    <label><span>Biến thể <small>Không bắt buộc</small></span><select name="product_variant_id"><option value="">Dùng cho sản phẩm</option>@foreach($product->variants as $variant)<option value="{{ $variant->id }}">{{ $variant->color_name }} · {{ $variant->sku }}</option>@endforeach</select></label>
                    <label><span>Văn bản thay thế <small>Không bắt buộc</small></span><input name="alt_text" maxlength="255"></label>
                    <label><span>Thứ tự</span><input name="sort_order" type="number" min="0" value="{{ $product->images->max('sort_order') + 1 }}" required></label>
                </div>
                <button class="admin-form-submit" type="submit">Tải ảnh lên</button>
            </form>
            <div class="admin-image-grid">
                @forelse($product->images as $image)
                    <article><img src="{{ $image->url }}" alt="{{ $image->alt_text ?? $product->name }}" width="320" height="320"><div><strong>#{{ $image->sort_order }} {{ $image->variant?->color_name ? '· '.$image->variant->color_name : '· Dùng chung' }}</strong><span>{{ $image->alt_text ?: 'Chưa có alt text' }}</span><small class="admin-image-source">{{ $image->disk }} · {{ $image->path }}</small><form method="POST" action="{{ route('admin.catalog.products.images.destroy', [$product, $image]) }}">@csrf @method('DELETE')<button type="submit">Xóa ảnh</button></form></div></article>
                @empty
                    <p class="admin-empty">Chưa có ảnh nào. Tải ít nhất hai ảnh để card storefront chuyển ảnh khi hover.</p>
                @endforelse
            </div>
        </section>

        <form class="admin-danger-zone" method="POST" action="{{ route('admin.catalog.products.destroy', $product) }}">
            @csrf @method('DELETE')
            <div><strong>Lưu trữ sản phẩm</strong><p>Sản phẩm bị soft-delete và không còn được bán; snapshot sản phẩm trong đơn cũ vẫn được giữ nguyên.</p></div>
            <button type="submit">Lưu trữ sản phẩm</button>
        </form>
    </section>
@endsection
