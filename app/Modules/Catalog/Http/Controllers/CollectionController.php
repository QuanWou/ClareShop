<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\ListCollectionProductsAction;
use App\Modules\Catalog\Http\Requests\ListCatalogProductsRequest;
use App\Modules\Catalog\Models\Category;
use Illuminate\Contracts\View\View;

class CollectionController extends Controller
{
    public function show(
        ListCatalogProductsRequest $request,
        Category $category,
        ListCollectionProductsAction $action,
    ): View {
        return view('catalog.collections.show', $action->execute($category, $request->safe()->except('category')));
    }
}
