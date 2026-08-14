<?php

namespace Tests\Feature;

use App\Modules\Appointments\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_appointment_form_is_available(): void
    {
        $this->get(route('appointments.create'))
            ->assertOk()
            ->assertSee('Cùng tìm một')
            ->assertSee('Yêu cầu lắp đặt')
            ->assertSee('data-appointment-form', false);
    }

    public function test_guest_can_submit_a_consultation_request(): void
    {
        $response = $this->post(route('appointments.store'), [
            ...$this->contactDetails(),
            'type' => 'consultation',
            'preferred_starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'customer_note' => 'Cần chọn đèn cho góc đọc sách.',
        ]);

        $appointment = Appointment::query()->firstOrFail();

        $response
            ->assertRedirectContains('/appointments/'.$appointment->number.'/complete')
            ->assertSessionHasNoErrors();

        $this->get($response->headers->get('Location'))
            ->assertOk()
            ->assertSee('Yêu cầu đã được ghi nhận')
            ->assertSee($appointment->number)
            ->assertSee('Chờ xác nhận');

        $this->assertDatabaseHas('appointments', [
            'number' => $appointment->number,
            'type' => 'consultation',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('appointment_status_histories', [
            'appointment_id' => $appointment->getKey(),
            'from_status' => null,
            'to_status' => 'pending',
        ]);
    }

    public function test_installation_request_requires_an_address(): void
    {
        $this->from(route('appointments.create', ['type' => 'installation']))
            ->post(route('appointments.store'), [
                ...$this->contactDetails(),
                'type' => 'installation',
                'preferred_starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('appointments.create', ['type' => 'installation']))
            ->assertSessionHasErrors(['address_line_1', 'ward', 'district', 'city']);

        $this->assertDatabaseCount('appointments', 0);
    }

    private function contactDetails(): array
    {
        return [
            'customer_name' => 'Nguyễn Minh An',
            'customer_email' => 'an@example.test',
            'customer_phone' => '0901234567',
        ];
    }
}
