@php
    $selectedAttributeFilters = collect($filters['attributes'] ?? []);
    $selectedAttributeCount = $selectedAttributeFilters
        ->flatten()
        ->filter(fn ($value) => filled($value))
        ->count();
    $hasPriceFilter = filled($filters['min_price'] ?? null) || filled($filters['max_price'] ?? null);
    $activeFilterCount = ($selectedCategory ? 1 : 0)
        + (filled($filters['brand'] ?? null) ? 1 : 0)
        + ($hasPriceFilter ? 1 : 0)
        + $selectedAttributeCount;
    $selectedBrand = $brands->firstWhere('slug', $filters['brand'] ?? '');
    $visibleCategories = $categories->filter(
        fn ($category) => $category->published_products_count > 0 || $selectedCategory?->is($category),
    );
@endphp

<aside class="catalog-filter-sidebar" id="catalog-filter-sidebar" aria-label="Bộ lọc sản phẩm" data-catalog-filter-panel>
    <form id="catalog-filter-form" method="GET" action="{{ route('catalog.products.index') }}">
        <input type="hidden" name="view" value="{{ $viewMode }}">

        <div class="catalog-filter-sidebar-heading">
            <div>
                <span>Bộ lọc</span>
                <strong>{{ $siteContent->get('collection_filter_label') }}</strong>
                @if ($activeFilterCount > 0)
                    <small class="catalog-filter-active-count">{{ $activeFilterCount }} đang chọn</small>
                @endif
            </div>
            <button type="button" aria-label="Đóng bộ lọc" data-catalog-filter-close>×</button>
        </div>

        <div class="catalog-filter-groups">
            <details class="catalog-filter-group" open data-catalog-filter-group>
                <summary>
                    <span>Danh mục</span>
                    <small>{{ $selectedCategory?->name ?? 'Tất cả đèn' }}</small>
                </summary>
                <div class="catalog-filter-group-content">
                    <label class="sr-only" for="catalog-category-filter">Chọn danh mục sản phẩm</label>
                    <select id="catalog-category-filter" name="category">
                        <option value="" @selected($selectedCategory === null)>Tất cả đèn ({{ $totalProductCount }})</option>
                        @foreach ($visibleCategories as $category)
                            <option value="{{ $category->slug }}" @selected($selectedCategory?->is($category))>
                                {{ $category->name }} ({{ $category->published_products_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </details>

            @if ($brands->isNotEmpty())
                <details class="catalog-filter-group" @if($selectedBrand) open @endif data-catalog-filter-group>
                    <summary>
                        <span>Thương hiệu</span>
                        <small>{{ $selectedBrand?->name ?? 'Tất cả' }}</small>
                    </summary>
                    <div class="catalog-filter-group-content">
                        <label class="sr-only" for="catalog-brand-filter">Chọn thương hiệu</label>
                        <select id="catalog-brand-filter" name="brand">
                            <option value="">Tất cả thương hiệu</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->slug }}" @selected(($filters['brand'] ?? '') === $brand->slug)>{{ $brand->name }} ({{ $brand->published_products_count }})</option>
                            @endforeach
                        </select>
                    </div>
                </details>
            @endif

            <details class="catalog-filter-group" @if($hasPriceFilter) open @endif data-catalog-filter-group>
                <summary>
                    <span>Khoảng giá</span>
                    <small>{{ $hasPriceFilter ? 'Đã đặt' : 'Tùy chọn' }}</small>
                </summary>
                <div class="catalog-filter-group-content">
                    <div class="catalog-price-filter">
                        <label><span>Từ</span><input name="min_price" type="number" min="0" step="10000" value="{{ $filters['min_price'] ?? '' }}" placeholder="0"></label>
                        <span aria-hidden="true">—</span>
                        <label><span>Đến</span><input name="max_price" type="number" min="0" step="10000" value="{{ $filters['max_price'] ?? '' }}" placeholder="{{ (int) $maximumCatalogPrice }}"></label>
                    </div>
                </div>
            </details>

            @foreach ($filterAttributes as $attribute)
                @php
                    $selectedValues = collect($selectedAttributeFilters->get($attribute->slug, []));
                    $visibleValues = $attribute->values->filter(
                        fn ($value) => $value->published_products_count > 0 || $selectedValues->contains($value->slug),
                    );
                @endphp
                <details class="catalog-filter-group" @if($selectedValues->isNotEmpty()) open @endif data-catalog-filter-group>
                    <summary>
                        <span>{{ $attribute->name }}@if($attribute->unit) <small>({{ $attribute->unit }})</small>@endif</span>
                        <small>{{ $selectedValues->isNotEmpty() ? $selectedValues->count().' đã chọn' : 'Tất cả' }}</small>
                    </summary>
                    <div class="catalog-filter-group-content">
                        <div @class(['catalog-filter-options', 'catalog-color-filter' => $attribute->filter_type === 'color'])>
                            @foreach ($visibleValues as $value)
                                <label>
                                    <input
                                        type="checkbox"
                                        name="attributes[{{ $attribute->slug }}][]"
                                        value="{{ $value->slug }}"
                                        @checked($selectedValues->contains($value->slug))
                                    >
                                    @if ($value->color_hex)<span class="catalog-filter-swatch" style="--filter-swatch: {{ $value->color_hex }}" aria-hidden="true"></span>@endif
                                    <span>{{ $value->label }}</span><small>{{ $value->published_products_count }}</small>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </details>
            @endforeach
        </div>

        <div class="catalog-filter-actions">
            <button class="button button-primary" type="submit">Xem sản phẩm</button>
            <a href="{{ route('catalog.products.index') }}">Gỡ bộ lọc</a>
        </div>
    </form>
</aside>
<button class="catalog-filter-backdrop" type="button" aria-label="Đóng bộ lọc" data-catalog-filter-close tabindex="-1"></button>
