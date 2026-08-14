<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Actions\GetStorefrontHomeAction;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(GetStorefrontHomeAction $action): View
    {
        return view('catalog.home', $action->execute());
    }
}
