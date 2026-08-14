<?php

namespace App\Modules\Admin\Actions;

use App\Modules\Appointments\Models\Appointment;

class ShowAdminAppointmentAction
{
    public function execute(Appointment $appointment): Appointment
    {
        return $appointment->load([
            'order',
            'statusHistories.changedBy',
        ]);
    }
}
