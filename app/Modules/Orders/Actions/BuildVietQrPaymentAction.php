<?php

namespace App\Modules\Orders\Actions;

use App\Modules\Orders\Data\VietQrPaymentData;
use App\Modules\Orders\Models\Order;

class BuildVietQrPaymentAction
{
    public function execute(Order $order): VietQrPaymentData
    {
        $vietQr = config('checkout.payment.vietqr');
        $transferContent = $order->number;
        $imagePath = implode('-', [
            $vietQr['bank_id'],
            $vietQr['account_number'],
            $vietQr['template'],
        ]).'.jpg';
        $query = http_build_query([
            'amount' => (int) $order->total,
            'addInfo' => $transferContent,
            'accountName' => $vietQr['account_name'],
        ], '', '&', PHP_QUERY_RFC3986);

        return new VietQrPaymentData(
            qrCodeUrl: rtrim($vietQr['image_base_url'], '/').'/'.$imagePath.'?'.$query,
            transferContent: $transferContent,
            bankId: $vietQr['bank_id'],
            accountNumber: $vietQr['account_number'],
            accountName: $vietQr['account_name'],
            amount: (int) $order->total,
        );
    }
}
