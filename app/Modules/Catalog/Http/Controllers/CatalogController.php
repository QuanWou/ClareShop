<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\ListPublishedProductsAction;
use App\Modules\Catalog\Http\Requests\ListCatalogProductsRequest;
use Illuminate\Contracts\View\View;

class CatalogController extends Controller
{
    public function index(ListCatalogProductsRequest $request, ListPublishedProductsAction $action): View
    {
        return view('catalog.products.index', $action->execute($request->validated()));
    }
}
