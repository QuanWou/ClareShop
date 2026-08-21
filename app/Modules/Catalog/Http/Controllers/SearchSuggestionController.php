<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\SuggestPublishedProductsAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    public function __invoke(Request $request, SuggestPublishedProductsAction $action): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        return response()->json(['data' => mb_strlen($term) >= 2 ? $action->execute($term) : []]);
    }
}
