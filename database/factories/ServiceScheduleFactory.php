<?php

namespace Database\Factories;

use App\Enums\SchedulingType;
use App\Models\Property;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceSchedule>
 */
class ServiceScheduleFactory extends Factory
{
    public function definition(): array
    {
        $schedulingType = fake()->randomElement(SchedulingType::cases());
        $frequency = fake()->randomElement(['weekly', 'biweekly', 'monthly', 'quarterly']);
        $startDate = fake()->dateTimeBetween('-2 months', 'now');
        $endDate = fake()->boolean(30) ? fake()->dateTimeBetween('+3 months', '+6 months') : null;

        return [
            'property_id' => Property::factory(),
            'service_type_id' => ServiceType::factory(),
            'scheduling_type' => $schedulingType,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'day_of_week' => in_array($frequency, ['weekly', 'biweekly']) ? fake()->numberBetween(0, 6) : null,
            'week_of_month' => $frequency === 'monthly' ? fake()->numberBetween(1, 4) : null,
            'is_active' => fake()->boolean(85),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduling_type' => SchedulingType::Manual,
            'frequency' => null,
            'day_of_week' => null,
            'week_of_month' => null,
            'end_date' => null,
        ]);
    }

    public function recurring(): static
    {
        $frequency = fake()->randomElement(['weekly', 'biweekly', 'monthly', 'quarterly']);

        return $this->state(fn (array $attributes) => [
            'scheduling_type' => SchedulingType::Recurring,
            'frequency' => $frequency,
            'day_of_week' => in_array($frequency, ['weekly', 'biweekly']) ? fake()->numberBetween(0, 6) : null,
            'week_of_month' => $frequency === 'monthly' ? fake()->numberBetween(1, 4) : null,
        ]);
    }

    public function seasonal(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduling_type' => SchedulingType::Seasonal,
            'frequency' => null,
            'day_of_week' => null,
            'week_of_month' => null,
        ]);
    }
}
