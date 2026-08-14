<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\ListAdminCatalogCategoriesAction;
use App\Modules\Catalog\Actions\ArchiveCatalogCategoryAction;
use App\Modules\Catalog\Actions\CreateCatalogCategoryAction;
use App\Modules\Catalog\Actions\UpdateCatalogCategoryAction;
use App\Modules\Catalog\Http\Requests\Admin\StoreCatalogCategoryRequest;
use App\Modules\Catalog\Http\Requests\Admin\UpdateCatalogCategoryRequest;
use App\Modules\Catalog\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminCatalogCategoryController extends Controller
{
    public function index(ListAdminCatalogCategoriesAction $listCategories): View
    {
        return view('admin.catalog.categories.index', [
            'categories' => $listCategories->execute(),
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.categories.form', ['category' => new Category()]);
    }

    public function store(StoreCatalogCategoryRequest $request, CreateCatalogCategoryAction $createCategory): RedirectResponse
    {
        $category = $createCategory->execute($request->validated());

        return redirect()->route('admin.catalog.categories.edit', $category)->with('success', 'Nhóm sản phẩm đã được tạo.');
    }

    public function edit(Category $category): View
    {
        return view('admin.catalog.categories.form', compact('category'));
    }

    public function update(
        UpdateCatalogCategoryRequest $request,
        Category $category,
        UpdateCatalogCategoryAction $updateCategory,
    ): RedirectResponse {
        $updateCategory->execute($category, $request->validated());

        return redirect()->route('admin.catalog.categories.edit', $category)->with('success', 'Nhóm sản phẩm đã được cập nhật.');
    }

    public function destroy(Category $category, ArchiveCatalogCategoryAction $archiveCategory): RedirectResponse
    {
        $archiveCategory->execute($category);

        return redirect()->route('admin.catalog.categories.index')->with('success', 'Nhóm sản phẩm đã được lưu trữ.');
    }
}
