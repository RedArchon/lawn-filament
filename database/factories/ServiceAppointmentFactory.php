<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\ServiceSchedule;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceAppointment>
 */
class ServiceAppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\ServiceAppointment $appointment) {
            // If company_id is set, use it to create related records
            if ($appointment->company_id) {
                $schedule = ServiceSchedule::factory()->create(['company_id' => $appointment->company_id]);
                $appointment->service_schedule_id = $schedule->id;
                $appointment->property_id = $schedule->property_id;
                $appointment->service_type_id = $schedule->service_type_id;
            } else {
                // Create fresh schedule with its own company
                $schedule = ServiceSchedule::factory()->create();
                $appointment->company_id = $schedule->company_id;
                $appointment->service_schedule_id = $schedule->id;
                $appointment->property_id = $schedule->property_id;
                $appointment->service_type_id = $schedule->service_type_id;
            }

            // Set other fields if not already set
            if (! $appointment->scheduled_date) {
                $appointment->scheduled_date = fake()->dateTimeBetween('now', '+30 days');
            }
            if (! $appointment->scheduled_time) {
                $appointment->scheduled_time = fake()->optional(0.5)->time('H:i');
            }
            if (! $appointment->status) {
                $appointment->status = fake()->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled', 'skipped']);
            }
            if ($appointment->status === 'completed' && ! $appointment->completed_at) {
                $appointment->completed_at = fake()->dateTimeBetween('-7 days', 'now');
            }
            if ($appointment->status === 'completed' && ! $appointment->duration_minutes) {
                $appointment->duration_minutes = fake()->numberBetween(20, 90);
            }
            if (! $appointment->notes) {
                $appointment->notes = fake()->optional(0.2)->sentence();
            }
        });
    }
}
