<?php

namespace App\Modules\Appointments\Actions;

use App\Modules\Appointments\Models\Appointment;

class ShowAppointmentAction
{
    public function execute(string $number): Appointment
    {
        return Appointment::query()
            ->where('number', $number)
            ->firstOrFail();
    }
}
