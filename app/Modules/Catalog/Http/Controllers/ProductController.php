<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\RecordRecentlyViewedProductAction;
use App\Modules\Catalog\Actions\ShowPublishedProductAction;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function show(Request $request, Product $product, ShowPublishedProductAction $action, RecordRecentlyViewedProductAction $recordView): View
    {
        $data = $action->execute($product);
        $recordView->execute($request->user(), $data['product']);

        return view('catalog.products.show', $data);
    }
}
