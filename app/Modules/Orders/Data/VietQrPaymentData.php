<?php

namespace App\Modules\Orders\Data;

readonly class VietQrPaymentData
{
    public function __construct(
        public string $qrCodeUrl,
        public string $transferContent,
        public string $bankId,
        public string $accountNumber,
        public string $accountName,
        public int $amount,
    ) {}

    public function toArray(): array
    {
        return [
            'qr_code_url' => $this->qrCodeUrl,
            'transfer_content' => $this->transferContent,
            'bank_id' => $this->bankId,
            'account_number' => $this->accountNumber,
            'account_name' => $this->accountName,
            'amount' => $this->amount,
        ];
    }
}
