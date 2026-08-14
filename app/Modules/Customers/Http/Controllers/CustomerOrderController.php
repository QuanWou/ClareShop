<?php

namespace App\Modules\Customers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Customers\Actions\ShowCustomerOrderAction;
use App\Modules\Orders\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CustomerOrderController extends Controller
{
    public function show(Request $request, Order $order, ShowCustomerOrderAction $showOrder): View
    {
        /** @var User $user */
        $user = $request->user();
        $order = $showOrder->execute($user, $order);

        return view('customers.orders.show', [
            'order' => $order,
            'payment' => $order->payments->sortByDesc('id')->first(),
        ]);
    }
}
