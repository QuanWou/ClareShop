<?php

namespace App\Modules\Orders\Actions;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class CalculateEstimatedDeliveryAtAction
{
    public function execute(CarbonInterface $placedAt, ?int $estimatedBusinessDays): ?CarbonImmutable
    {
        if ($estimatedBusinessDays === null) {
            return null;
        }

        $localPlacedAt = CarbonImmutable::instance($placedAt)->setTimezone(config('app.timezone'));
        $dispatchDay = $this->nextWorkingDay(
            $localPlacedAt->hour >= config('checkout.shipping.estimate.order_cutoff_hour')
                ? $localPlacedAt->addDay()
                : $localPlacedAt,
        )->startOfDay();

        $deliveryDay = $dispatchDay;

        for ($day = 0; $day < $estimatedBusinessDays; $day++) {
            $deliveryDay = $this->nextWorkingDay($deliveryDay->addDay())->startOfDay();
        }

        return $deliveryDay->setTime(18, 0);
    }

    private function nextWorkingDay(CarbonImmutable $date): CarbonImmutable
    {
        while ($date->isWeekend()) {
            $date = $date->addDay();
        }

        return $date;
    }
}
