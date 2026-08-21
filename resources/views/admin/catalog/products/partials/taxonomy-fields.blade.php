@php
    $managedProduct = $product ?? null;
    $selectedCategoryIds = collect(old(
        'category_ids',
        $managedProduct?->categories?->pluck('id')->all() ?? array_filter([$managedProduct?->category_id]),
    ))->map(fn ($id) => (string) $id);
    $selectedAttributeValueIds = collect(old(
        'attribute_value_ids',
        $managedProduct?->attributeValues?->pluck('id')->all() ?? [],
    ))->map(fn ($id) => (string) $id);
@endphp

<label>
    <span>Danh mục chính</span>
    <select name="category_id">
        <option value="">Chưa chọn</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) old('category_id', $managedProduct?->category_id) === (string) $category->id)>
                {{ str_repeat('— ', (int) $category->tree_depth) }}{{ $category->name }}
            </option>
        @endforeach
    </select>
</label>

<label>
    <span>Thương hiệu</span>
    <select name="brand_id">
        <option value="">Chưa chọn thương hiệu</option>
        @foreach ($brands as $brand)
            <option value="{{ $brand->id }}" @selected((string) old('brand_id', $managedProduct?->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
        @endforeach
    </select>
</label>

<fieldset class="admin-form-full admin-option-fieldset">
    <legend>Danh mục liên quan <small>Một sản phẩm có thể xuất hiện trong nhiều nhóm</small></legend>
    <div class="admin-option-grid">
        @foreach ($categories as $category)
            <label style="--option-depth: {{ $category->tree_depth }}">
                <input name="category_ids[]" type="checkbox" value="{{ $category->id }}" @checked($selectedCategoryIds->contains((string) $category->id))>
                <span>{{ str_repeat('— ', (int) $category->tree_depth) }}{{ $category->name }}</span>
            </label>
        @endforeach
    </div>
</fieldset>

@if ($attributes->isNotEmpty())
    <fieldset class="admin-form-full admin-attribute-assignment">
        <legend>Thuộc tính &amp; thông số</legend>
        <div class="admin-attribute-assignment-grid">
            @foreach ($attributes as $attribute)
                <section>
                    <strong>{{ $attribute->name }}@if($attribute->unit) <small>({{ $attribute->unit }})</small>@endif</strong>
                    <div>
                        @forelse ($attribute->values as $value)
                            <label>
                                <input name="attribute_value_ids[]" type="checkbox" value="{{ $value->id }}" @checked($selectedAttributeValueIds->contains((string) $value->id))>
                                @if ($value->color_hex)<span class="admin-attribute-color" style="--attribute-color: {{ $value->color_hex }}" aria-hidden="true"></span>@endif
                                <span>{{ $value->label }}</span>
                            </label>
                        @empty
                            <small>Chưa có giá trị. Hãy bổ sung tại trang Thuộc tính.</small>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </fieldset>
@endif

<label><span>SEO title</span><input name="seo_title" maxlength="255" value="{{ old('seo_title', $managedProduct?->seo_title) }}"></label>
<label><span>SEO description</span><textarea name="seo_description" rows="3" maxlength="500">{{ old('seo_description', $managedProduct?->seo_description) }}</textarea></label>
