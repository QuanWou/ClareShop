@php
    $fieldName = fn (string $field): string => $prefix === '' ? $field : $prefix.'['.$field.']';
    $oldKey = fn (string $field): string => $prefix === '' ? $field : $prefix.'.'.$field;
    $value = fn (string $field, mixed $fallback = null): mixed => old($oldKey($field), $variant?->{$field} ?? $fallback);
@endphp

<div class="admin-form-grid admin-variant-grid">
    <label><span>SKU</span><input name="{{ $fieldName('sku') }}" value="{{ $value('sku') }}" required></label>
    <label><span>Tên màu</span><input name="{{ $fieldName('color_name') }}" value="{{ $value('color_name') }}" required></label>
    <label><span>Mã màu <small>Ví dụ #8A3E45</small></span><input name="{{ $fieldName('color_hex') }}" value="{{ $value('color_hex') }}" placeholder="#8A3E45" pattern="#[0-9A-Fa-f]{6}"></label>
    <label><span>Giá bán (VND)</span><input name="{{ $fieldName('price') }}" type="number" min="1" step="1" value="{{ $value('price') }}" required></label>
    <label><span>Giá tham chiếu <small>Không bắt buộc</small></span><input name="{{ $fieldName('compare_at_price') }}" type="number" min="1" step="1" value="{{ $value('compare_at_price') }}"></label>
    <label><span>Tồn kho</span><input name="{{ $fieldName('stock_quantity') }}" type="number" min="0" step="1" value="{{ $value('stock_quantity', 0) }}" required></label>
    <label><span>Trọng lượng (gram)</span><input name="{{ $fieldName('weight_grams') }}" type="number" min="1" step="1" value="{{ $value('weight_grams', 1000) }}" required></label>
    <label><span>Thứ tự</span><input name="{{ $fieldName('sort_order') }}" type="number" min="0" step="1" value="{{ $value('sort_order', 0) }}" required></label>
    <label><span>Trạng thái</span><input name="{{ $fieldName('is_active') }}" type="hidden" value="0"><span class="admin-checkbox"><input name="{{ $fieldName('is_active') }}" type="checkbox" value="1" @checked((bool) $value('is_active', true))> Biến thể đang bán</span></label>
</div>
