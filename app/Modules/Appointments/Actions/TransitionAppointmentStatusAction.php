<?php

namespace App\Modules\Appointments\Actions;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Models\AppointmentStatusHistory;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionAppointmentStatusAction
{
    private const ALLOWED_TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
    ];

    public function execute(Appointment $appointment, int $actorId, array $validated): Appointment
    {
        return DB::transaction(function () use ($appointment, $actorId, $validated): Appointment {
            $lockedAppointment = Appointment::query()
                ->lockForUpdate()
                ->findOrFail($appointment->getKey());
            $currentStatus = $lockedAppointment->status;
            $nextStatus = $validated['status'];

            $this->ensureTransitionIsAllowed($lockedAppointment, $nextStatus);

            $attributes = [
                'status' => $nextStatus,
                'internal_note' => $validated['internal_note'] ?? $lockedAppointment->internal_note,
            ];

            if ($nextStatus === 'confirmed') {
                $attributes = [
                    ...$attributes,
                    'scheduled_starts_at' => $validated['scheduled_starts_at'],
                    'scheduled_ends_at' => $validated['scheduled_ends_at'] ?? null,
                    'confirmed_by' => $actorId,
                    'confirmed_at' => now(),
                ];

                if (filled($validated['order_number'] ?? null)) {
                    $attributes['order_id'] = Order::query()
                        ->where('number', $validated['order_number'])
                        ->value('id');
                }
            }

            if ($nextStatus === 'cancelled') {
                $attributes['cancelled_at'] = now();
            }

            $lockedAppointment->update($attributes);

            AppointmentStatusHistory::query()->create([
                'appointment_id' => $lockedAppointment->getKey(),
                'from_status' => $currentStatus,
                'to_status' => $nextStatus,
                'changed_by' => $actorId,
                'note' => $validated['internal_note'] ?? null,
            ]);

            return $lockedAppointment->fresh(['order', 'statusHistories.changedBy']);
        });
    }

    public function allowedNextStatuses(Appointment $appointment): array
    {
        return self::ALLOWED_TRANSITIONS[$appointment->status] ?? [];
    }

    private function ensureTransitionIsAllowed(Appointment $appointment, string $nextStatus): void
    {
        if (! in_array($nextStatus, self::ALLOWED_TRANSITIONS[$appointment->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => 'Không thể chuyển yêu cầu từ trạng thái hiện tại sang trạng thái đã chọn.',
            ]);
        }
    }
}
