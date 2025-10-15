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
        $status = fake()->randomElement(['scheduled', 'in_progress', 'completed', 'cancelled', 'skipped']);
        $schedule = ServiceSchedule::factory()->create();

        return [
            'company_id' => $schedule->company_id,
            'service_schedule_id' => $schedule->id,
            'property_id' => $schedule->property_id,
            'service_type_id' => $schedule->service_type_id,
            'scheduled_date' => fake()->dateTimeBetween('now', '+30 days'),
            'scheduled_time' => fake()->optional(0.5)->time('H:i'),
            'status' => $status,
            'completed_at' => $status === 'completed' ? fake()->dateTimeBetween('-7 days', 'now') : null,
            'completed_by' => null,
            'duration_minutes' => $status === 'completed' ? fake()->numberBetween(20, 90) : null,
            'notes' => fake()->optional(0.2)->sentence(),
        ];
    }
}
