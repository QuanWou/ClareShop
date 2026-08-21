@php
    $selectedAttributeFilters = collect($filters['attributes'] ?? []);
@endphp

<aside class="catalog-filter-sidebar" id="catalog-filter-sidebar" aria-label="Bộ lọc sản phẩm" data-catalog-filter-panel>
    <form id="catalog-filter-form" method="GET" action="{{ route('catalog.products.index') }}">
        <input type="hidden" name="view" value="{{ $viewMode }}">

        <div class="catalog-filter-sidebar-heading">
            <div><span>Bộ lọc</span><strong>{{ $siteContent->get('collection_filter_label') }}</strong></div>
            <button type="button" aria-label="Đóng bộ lọc" data-catalog-filter-close>×</button>
        </div>

        <fieldset>
            <legend>Danh mục</legend>
            <div class="catalog-filter-options catalog-filter-category-options">
                <label>
                    <input type="radio" name="category" value="" @checked($selectedCategory === null)>
                    <span>Tất cả đèn</span><small>{{ $totalProductCount }}</small>
                </label>
                @foreach ($categories as $category)
                    <label style="--filter-depth: {{ $category->tree_depth }}">
                        <input type="radio" name="category" value="{{ $category->slug }}" @checked($selectedCategory?->is($category))>
                        <span>{{ $category->name }}</span><small>{{ $category->published_products_count }}</small>
                    </label>
                @endforeach
            </div>
        </fieldset>

        @if ($brands->isNotEmpty())
            <fieldset>
                <legend>Thương hiệu</legend>
                <select name="brand">
                    <option value="">Tất cả thương hiệu</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->slug }}" @selected(($filters['brand'] ?? '') === $brand->slug)>{{ $brand->name }} ({{ $brand->published_products_count }})</option>
                    @endforeach
                </select>
            </fieldset>
        @endif

        <fieldset>
            <legend>Khoảng giá</legend>
            <div class="catalog-price-filter">
                <label><span>Từ</span><input name="min_price" type="number" min="0" step="10000" value="{{ $filters['min_price'] ?? '' }}" placeholder="0"></label>
                <span aria-hidden="true">—</span>
                <label><span>Đến</span><input name="max_price" type="number" min="0" step="10000" value="{{ $filters['max_price'] ?? '' }}" placeholder="{{ (int) $maximumCatalogPrice }}"></label>
            </div>
        </fieldset>

        @foreach ($filterAttributes as $attribute)
            <fieldset>
                <legend>{{ $attribute->name }}@if($attribute->unit) <small>({{ $attribute->unit }})</small>@endif</legend>
                <div @class(['catalog-filter-options', 'catalog-color-filter' => $attribute->filter_type === 'color'])>
                    @foreach ($attribute->values as $value)
                        <label>
                            <input
                                type="checkbox"
                                name="attributes[{{ $attribute->slug }}][]"
                                value="{{ $value->slug }}"
                                @checked(collect($selectedAttributeFilters->get($attribute->slug, []))->contains($value->slug))
                            >
                            @if ($value->color_hex)<span class="catalog-filter-swatch" style="--filter-swatch: {{ $value->color_hex }}" aria-hidden="true"></span>@endif
                            <span>{{ $value->label }}</span><small>{{ $value->published_products_count }}</small>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <div class="catalog-filter-actions">
            <button class="button button-primary" type="submit">Xem sản phẩm</button>
            <a href="{{ route('catalog.products.index') }}">Gỡ bộ lọc</a>
        </div>
    </form>
</aside>
<button class="catalog-filter-backdrop" type="button" aria-label="Đóng bộ lọc" data-catalog-filter-close tabindex="-1"></button>
