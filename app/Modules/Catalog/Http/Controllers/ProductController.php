<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\ShowPublishedProductAction;
use App\Modules\Catalog\Models\Product;
use Illuminate\Contracts\View\View;

class ProductController extends Controller
{
    public function show(Product $product, ShowPublishedProductAction $action): View
    {
        return view('catalog.products.show', $action->execute($product));
    }
}
