<?php

namespace App\Modules\Appointments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Appointments\Actions\CreateAppointmentAction;
use App\Modules\Appointments\Actions\ShowAppointmentAction;
use App\Modules\Appointments\Http\Requests\StoreAppointmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AppointmentController extends Controller
{
    public function create(Request $request): View
    {
        $requestedType = $request->string('type')->value();

        return view('appointments.create', [
            'requestedType' => in_array($requestedType, ['consultation', 'installation'], true)
                ? $requestedType
                : 'consultation',
        ]);
    }

    public function store(
        StoreAppointmentRequest $request,
        CreateAppointmentAction $createAppointment,
    ): RedirectResponse {
        $appointment = $createAppointment->execute(
            userId: $this->userId($request),
            validated: $request->validated(),
        );

        return redirect()->to(URL::temporarySignedRoute(
            'appointments.complete',
            now()->addDays(7),
            ['appointmentNumber' => $appointment->number],
        ));
    }

    public function complete(string $appointmentNumber, ShowAppointmentAction $showAppointment): View
    {
        return view('appointments.complete', [
            'appointment' => $showAppointment->execute($appointmentNumber),
        ]);
    }

    private function userId(Request $request): ?int
    {
        $identifier = $request->user()?->getAuthIdentifier();

        return $identifier === null ? null : (int) $identifier;
    }
}
