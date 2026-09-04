<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\ListAdminCatalogAttributesAction;
use App\Modules\Catalog\Actions\CreateCatalogAttributeAction;
use App\Modules\Catalog\Actions\CreateCatalogAttributeValueAction;
use App\Modules\Catalog\Actions\DeleteCatalogAttributeAction;
use App\Modules\Catalog\Actions\DeleteCatalogAttributeValueAction;
use App\Modules\Catalog\Actions\UpdateCatalogAttributeAction;
use App\Modules\Catalog\Actions\UpdateCatalogAttributeValueAction;
use App\Modules\Catalog\Http\Requests\Admin\StoreCatalogAttributeRequest;
use App\Modules\Catalog\Http\Requests\Admin\StoreCatalogAttributeValueRequest;
use App\Modules\Catalog\Http\Requests\Admin\UpdateCatalogAttributeRequest;
use App\Modules\Catalog\Http\Requests\Admin\UpdateCatalogAttributeValueRequest;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminCatalogAttributeController extends Controller
{
    public function index(ListAdminCatalogAttributesAction $listAttributes): View
    {
        return view('admin.catalog.attributes.index', ['attributes' => $listAttributes->execute()]);
    }

    public function create(): View
    {
        return view('admin.catalog.attributes.form', ['attribute' => new ProductAttribute]);
    }

    public function store(StoreCatalogAttributeRequest $request, CreateCatalogAttributeAction $createAttribute): RedirectResponse
    {
        $attribute = $createAttribute->execute($request->validated());

        return redirect()->route('admin.catalog.attributes.edit', $attribute)->with('success', 'Thuộc tính đã được tạo. Hãy thêm các giá trị có thể chọn.');
    }

    public function edit(ProductAttribute $attribute): View
    {
        return view('admin.catalog.attributes.form', [
            'attribute' => $attribute->load(['values' => fn ($query) => $query->withCount('products')]),
        ]);
    }

    public function update(
        UpdateCatalogAttributeRequest $request,
        ProductAttribute $attribute,
        UpdateCatalogAttributeAction $updateAttribute,
    ): RedirectResponse {
        $updateAttribute->execute($attribute, $request->validated());

        return redirect()->route('admin.catalog.attributes.edit', $attribute)->with('success', 'Thuộc tính đã được cập nhật.');
    }

    public function destroy(ProductAttribute $attribute, DeleteCatalogAttributeAction $deleteAttribute): RedirectResponse
    {
        $deleteAttribute->execute($attribute);

        return redirect()->route('admin.catalog.attributes.index')->with('success', 'Thuộc tính và các giá trị chưa dùng đã được xóa.');
    }

    public function storeValue(
        StoreCatalogAttributeValueRequest $request,
        ProductAttribute $attribute,
        CreateCatalogAttributeValueAction $createValue,
    ): RedirectResponse {
        $createValue->execute($attribute, $request->validated());

        return redirect()->route('admin.catalog.attributes.edit', $attribute)->with('success', 'Giá trị thuộc tính đã được thêm.');
    }

    public function updateValue(
        UpdateCatalogAttributeValueRequest $request,
        ProductAttribute $attribute,
        ProductAttributeValue $value,
        UpdateCatalogAttributeValueAction $updateValue,
    ): RedirectResponse {
        $updateValue->execute($value, $request->validated());

        return redirect()->route('admin.catalog.attributes.edit', $attribute)->with('success', 'Giá trị thuộc tính đã được cập nhật.');
    }

    public function destroyValue(
        ProductAttribute $attribute,
        ProductAttributeValue $value,
        DeleteCatalogAttributeValueAction $deleteValue,
    ): RedirectResponse {
        $deleteValue->execute($value);

        return redirect()->route('admin.catalog.attributes.edit', $attribute)->with('success', 'Giá trị thuộc tính đã được xóa.');
    }
}
