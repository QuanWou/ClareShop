<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\ListAdminCatalogCategoriesAction;
use App\Modules\Admin\Actions\ListAdminCatalogProductsAction;
use App\Modules\Admin\Actions\ShowAdminCatalogProductAction;
use App\Modules\Admin\Http\Requests\ListAdminCatalogProductsRequest;
use App\Modules\Catalog\Actions\ArchiveCatalogProductAction;
use App\Modules\Catalog\Actions\ArchiveCatalogVariantAction;
use App\Modules\Catalog\Actions\CreateCatalogProductAction;
use App\Modules\Catalog\Actions\CreateCatalogVariantAction;
use App\Modules\Catalog\Actions\DeleteCatalogProductImageAction;
use App\Modules\Catalog\Actions\RestoreCatalogProductAction;
use App\Modules\Catalog\Actions\RestoreCatalogVariantAction;
use App\Modules\Catalog\Actions\UpdateCatalogProductAction;
use App\Modules\Catalog\Actions\UpdateCatalogVariantAction;
use App\Modules\Catalog\Actions\UploadCatalogProductImageAction;
use App\Modules\Catalog\Http\Requests\Admin\StoreCatalogProductImageRequest;
use App\Modules\Catalog\Http\Requests\Admin\StoreCatalogProductRequest;
use App\Modules\Catalog\Http\Requests\Admin\StoreCatalogVariantRequest;
use App\Modules\Catalog\Http\Requests\Admin\UpdateCatalogProductRequest;
use App\Modules\Catalog\Http\Requests\Admin\UpdateCatalogVariantRequest;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminCatalogProductController extends Controller
{
    public function index(
        ListAdminCatalogProductsRequest $request,
        ListAdminCatalogProductsAction $listProducts,
        ListAdminCatalogCategoriesAction $listCategories,
    ): View {
        return view('admin.catalog.products.index', [
            'products' => $listProducts->execute($request->validated()),
            'categories' => $listCategories->execute(),
            'filters' => $request->validated(),
        ]);
    }

    public function create(ListAdminCatalogCategoriesAction $listCategories): View
    {
        return view('admin.catalog.products.create', [
            'categories' => $listCategories->execute(),
        ]);
    }

    public function store(StoreCatalogProductRequest $request, CreateCatalogProductAction $createProduct): RedirectResponse
    {
        $product = $createProduct->execute($request->validated());

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Sản phẩm và biến thể đầu tiên đã được tạo.');
    }

    public function edit(
        Product $product,
        ShowAdminCatalogProductAction $showProduct,
        ListAdminCatalogCategoriesAction $listCategories,
    ): View {
        return view('admin.catalog.products.edit', [
            'product' => $showProduct->execute($product),
            'categories' => $listCategories->execute(),
        ]);
    }

    public function update(
        UpdateCatalogProductRequest $request,
        Product $product,
        UpdateCatalogProductAction $updateProduct,
    ): RedirectResponse {
        $updateProduct->execute($product, $request->validated());

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Thông tin sản phẩm đã được cập nhật.');
    }

    public function destroy(Product $product, ArchiveCatalogProductAction $archiveProduct): RedirectResponse
    {
        $archiveProduct->execute($product);

        return redirect()->route('admin.catalog.products.index')->with('success', 'Sản phẩm đã được lưu trữ và sẽ không còn hiển thị tại cửa hàng.');
    }

    public function restore(Product $product, RestoreCatalogProductAction $restoreProduct): RedirectResponse
    {
        $restoreProduct->execute($product);

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Sản phẩm đã được khôi phục. Trạng thái bán và thời điểm xuất bản được giữ nguyên.');
    }

    public function storeVariant(
        StoreCatalogVariantRequest $request,
        Product $product,
        CreateCatalogVariantAction $createVariant,
    ): RedirectResponse {
        $createVariant->execute($product, $request->validated());

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Biến thể đã được thêm.');
    }

    public function updateVariant(
        UpdateCatalogVariantRequest $request,
        Product $product,
        ProductVariant $variant,
        UpdateCatalogVariantAction $updateVariant,
    ): RedirectResponse {
        $updateVariant->execute($variant, $request->validated());

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Biến thể đã được cập nhật.');
    }

    public function destroyVariant(
        Product $product,
        ProductVariant $variant,
        ArchiveCatalogVariantAction $archiveVariant,
    ): RedirectResponse {
        $archiveVariant->execute($variant);

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Biến thể đã được lưu trữ.');
    }

    public function restoreVariant(
        Product $product,
        ProductVariant $variant,
        RestoreCatalogVariantAction $restoreVariant,
    ): RedirectResponse {
        $restoreVariant->execute($variant);

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Biến thể đã được khôi phục với giá, tồn kho và trạng thái trước đó.');
    }

    public function storeImage(
        StoreCatalogProductImageRequest $request,
        Product $product,
        UploadCatalogProductImageAction $uploadImage,
    ): RedirectResponse {
        $uploadImage->execute($product, $request->validated());

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Ảnh sản phẩm đã được tải lên.');
    }

    public function destroyImage(
        Product $product,
        ProductImage $image,
        DeleteCatalogProductImageAction $deleteImage,
    ): RedirectResponse {
        $deleteImage->execute($image);

        return redirect()->route('admin.catalog.products.edit', $product)->with('success', 'Ảnh sản phẩm đã được xóa.');
    }
}
