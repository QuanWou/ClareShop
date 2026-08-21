<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\BuildAdminSalesReportAction;
use App\Modules\Admin\Http\Requests\AdminReportRequest;
use Illuminate\Contracts\View\View;

class AdminReportController extends Controller
{
    public function index(AdminReportRequest $request, BuildAdminSalesReportAction $action): View
    {
        return view('admin.reports.index', $action->execute($request->validated()));
    }
}
