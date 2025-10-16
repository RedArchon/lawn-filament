<?php

namespace Database\Factories;

use App\Models\ServiceSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceAppointment>
 */
class ServiceAppointmentFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled', 'skipped']);

        return [
            'scheduled_date' => fake()->dateTimeBetween('now', '+30 days'),
            'scheduled_time' => fake()->optional(0.5)->time('H:i'),
            'status' => $status,
            'completed_at' => $status === 'completed' ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'completed_by' => null,
            'duration_minutes' => $status === 'completed' ? fake()->numberBetween(20, 90) : null,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\ServiceAppointment $appointment) {
            // Create or use existing schedule based on company_id
            $schedule = ServiceSchedule::factory()->create(
                $appointment->company_id ? ['company_id' => $appointment->company_id] : []
            );

            $appointment->company_id = $schedule->company_id;
            $appointment->service_schedule_id = $schedule->id;
            $appointment->property_id = $schedule->property_id;
            $appointment->service_type_id = $schedule->service_type_id;
        });
    }
}
