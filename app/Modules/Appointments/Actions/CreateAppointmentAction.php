<?php

namespace App\Modules\Appointments\Actions;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Models\AppointmentStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateAppointmentAction
{
    public function execute(?int $userId, array $validated): Appointment
    {
        return DB::transaction(function () use ($userId, $validated): Appointment {
            $appointment = Appointment::query()->create([
                'number' => $this->generateNumber(),
                'user_id' => $userId,
                'order_id' => $validated['order_id'] ?? null,
                'type' => $validated['type'],
                'status' => 'pending',
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'preferred_starts_at' => $validated['preferred_starts_at'],
                'preferred_ends_at' => $validated['preferred_ends_at'] ?? null,
                'address_line_1' => $validated['address_line_1'] ?? null,
                'address_line_2' => $validated['address_line_2'] ?? null,
                'ward' => $validated['ward'] ?? null,
                'district' => $validated['district'] ?? null,
                'city' => $validated['city'] ?? null,
                'country_code' => $validated['country_code'] ?? 'VN',
                'customer_note' => $validated['customer_note'] ?? null,
            ]);

            AppointmentStatusHistory::query()->create([
                'appointment_id' => $appointment->getKey(),
                'from_status' => null,
                'to_status' => 'pending',
                'changed_by' => $userId,
                'note' => 'Yêu cầu dịch vụ được tạo từ storefront.',
            ]);

            return $appointment;
        });
    }

    private function generateNumber(): string
    {
        do {
            $number = 'CLR-SVC-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (Appointment::query()->where('number', $number)->exists());

        return $number;
    }
}
