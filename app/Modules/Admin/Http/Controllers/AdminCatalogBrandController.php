<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\ListAdminCatalogBrandsAction;
use App\Modules\Catalog\Actions\ArchiveCatalogBrandAction;
use App\Modules\Catalog\Actions\CreateCatalogBrandAction;
use App\Modules\Catalog\Actions\UpdateCatalogBrandAction;
use App\Modules\Catalog\Http\Requests\Admin\StoreCatalogBrandRequest;
use App\Modules\Catalog\Http\Requests\Admin\UpdateCatalogBrandRequest;
use App\Modules\Catalog\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminCatalogBrandController extends Controller
{
    public function index(ListAdminCatalogBrandsAction $listBrands): View
    {
        return view('admin.catalog.brands.index', ['brands' => $listBrands->execute()]);
    }

    public function create(): View
    {
        return view('admin.catalog.brands.form', ['brand' => new Brand]);
    }

    public function store(StoreCatalogBrandRequest $request, CreateCatalogBrandAction $createBrand): RedirectResponse
    {
        $brand = $createBrand->execute($request->validated());

        return redirect()->route('admin.catalog.brands.edit', $brand)->with('success', 'Thương hiệu đã được tạo.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.catalog.brands.form', compact('brand'));
    }

    public function update(
        UpdateCatalogBrandRequest $request,
        Brand $brand,
        UpdateCatalogBrandAction $updateBrand,
    ): RedirectResponse {
        $updateBrand->execute($brand, $request->validated());

        return redirect()->route('admin.catalog.brands.edit', $brand)->with('success', 'Thương hiệu đã được cập nhật.');
    }

    public function destroy(Brand $brand, ArchiveCatalogBrandAction $archiveBrand): RedirectResponse
    {
        $archiveBrand->execute($brand);

        return redirect()->route('admin.catalog.brands.index')->with('success', 'Thương hiệu đã được lưu trữ.');
    }
}
