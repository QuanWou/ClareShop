<?php

use App\Modules\Orders\Actions\ExpirePendingPayPalPaymentsAction;
use App\Modules\Orders\Actions\ExpirePendingQrPaymentsAction;
use App\Modules\Orders\Actions\ReconcilePendingPayOsPaymentsAction;
use App\Modules\Promotions\Actions\ExpirePendingVoucherReservationsAction;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(ExpirePendingPayPalPaymentsAction::class)->execute())
    ->name('payments:expire-paypal')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => app(ExpirePendingQrPaymentsAction::class)->execute())
    ->name('payments:expire-qr')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::call(fn () => app(ReconcilePendingPayOsPaymentsAction::class)->execute())
    ->name('payments:reconcile-payos')
    ->everyFifteenSeconds()
    ->withoutOverlapping();

Schedule::call(fn () => app(ExpirePendingVoucherReservationsAction::class)->execute())
    ->name('vouchers:expire-reservations')
    ->everyMinute()
    ->withoutOverlapping();
