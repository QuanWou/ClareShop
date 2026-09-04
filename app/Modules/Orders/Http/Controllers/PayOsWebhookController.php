<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Actions\HandlePayOsWebhookAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayOsWebhookController extends Controller
{
    public function __invoke(Request $request, HandlePayOsWebhookAction $handleWebhook): JsonResponse
    {
        $event = $handleWebhook->execute($request->all());

        return response()->json(['received' => true, 'status' => $event->status]);
    }
}
