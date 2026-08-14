<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\BuildAdminDashboardAction;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(BuildAdminDashboardAction $buildDashboard): View
    {
        return view('admin.dashboard', $buildDashboard->execute());
    }
}
