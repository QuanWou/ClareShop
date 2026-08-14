<?php

namespace App\Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Actions\ListAdminAppointmentsAction;
use App\Modules\Admin\Actions\ShowAdminAppointmentAction;
use App\Modules\Admin\Http\Requests\ListAdminAppointmentsRequest;
use App\Modules\Admin\Http\Requests\UpdateAdminAppointmentStatusRequest;
use App\Modules\Appointments\Actions\TransitionAppointmentStatusAction;
use App\Modules\Appointments\Models\Appointment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminAppointmentController extends Controller
{
    public function index(ListAdminAppointmentsRequest $request, ListAdminAppointmentsAction $listAppointments): View
    {
        return view('admin.appointments.index', [
            'appointments' => $listAppointments->execute($request->validated()),
            'filters' => $request->validated(),
        ]);
    }

    public function show(
        Appointment $appointment,
        ShowAdminAppointmentAction $showAppointment,
        TransitionAppointmentStatusAction $transitionAppointment,
    ): View {
        $appointment = $showAppointment->execute($appointment);

        return view('admin.appointments.show', [
            'appointment' => $appointment,
            'nextStatuses' => $transitionAppointment->allowedNextStatuses($appointment),
        ]);
    }

    public function updateStatus(
        UpdateAdminAppointmentStatusRequest $request,
        Appointment $appointment,
        TransitionAppointmentStatusAction $transitionAppointment,
    ): RedirectResponse {
        $transitionAppointment->execute(
            appointment: $appointment,
            actorId: (int) $request->user()->getAuthIdentifier(),
            validated: $request->validated(),
        );

        return redirect()
            ->route('admin.appointments.show', $appointment)
            ->with('success', 'Yêu cầu dịch vụ đã được cập nhật.');
    }
}
