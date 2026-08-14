<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\SearchPublishedProductsAction;
use App\Modules\Catalog\Http\Requests\SearchProductsRequest;
use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function __invoke(SearchProductsRequest $request, SearchPublishedProductsAction $action): View
    {
        return view('catalog.search', $action->execute($request->validated('q')));
    }
}
