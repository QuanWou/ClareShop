<?php

namespace App\Modules\Orders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Orders\Actions\HandlePayPalWebhookAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, HandlePayPalWebhookAction $handleWebhook): JsonResponse
    {
        $event = $handleWebhook->execute([
            'paypal-auth-algo' => $request->header('PayPal-Auth-Algo'),
            'paypal-cert-url' => $request->header('PayPal-Cert-Url'),
            'paypal-transmission-id' => $request->header('PayPal-Transmission-Id'),
            'paypal-transmission-sig' => $request->header('PayPal-Transmission-Sig'),
            'paypal-transmission-time' => $request->header('PayPal-Transmission-Time'),
        ], $request->json()->all());

        return response()->json([
            'received' => true,
            'status' => $event->status,
        ]);
    }
}
