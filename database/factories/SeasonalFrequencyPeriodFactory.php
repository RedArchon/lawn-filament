<?php

namespace Database\Factories;

use App\Enums\ServiceFrequency;
use App\Models\ServiceSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SeasonalFrequencyPeriod>
 */
class SeasonalFrequencyPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $schedule = ServiceSchedule::factory()->create();

        return [
            'company_id' => $schedule->company_id,
            'service_schedule_id' => $schedule->id,
            'start_month' => fake()->numberBetween(1, 12),
            'start_day' => 1,
            'end_month' => fake()->numberBetween(1, 12),
            'end_day' => 28,
            'frequency' => fake()->randomElement(ServiceFrequency::cases()),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Brooksville, FL Lawn Mowing Periods
     * Returns array of period definitions to create
     */
    public static function brooksvilleLawnCarePeriods(): array
    {
        return [
            // December 1 - January 31: Every 3 weeks (dormant period)
            [
                'start_month' => 12,
                'start_day' => 1,
                'end_month' => 1,
                'end_day' => 31,
                'frequency' => ServiceFrequency::Every3Weeks,
                'notes' => 'Dormant period - grass mostly dormant',
            ],
            // February 1 - March 31: Weekly (warming up)
            [
                'start_month' => 2,
                'start_day' => 1,
                'end_month' => 3,
                'end_day' => 31,
                'frequency' => ServiceFrequency::Weekly,
                'notes' => 'Warming up - begin regular mowing',
            ],
            // April 1 - September 30: Every 5 days (heavy growth)
            [
                'start_month' => 4,
                'start_day' => 1,
                'end_month' => 9,
                'end_day' => 30,
                'frequency' => ServiceFrequency::Every5Days,
                'notes' => 'Heavy growth period - frequent mowing needed',
            ],
            // October 1 - November 30: Biweekly (slowing down)
            [
                'start_month' => 10,
                'start_day' => 1,
                'end_month' => 11,
                'end_day' => 30,
                'frequency' => ServiceFrequency::Biweekly,
                'notes' => 'Growth slows - less frequent mowing',
            ],
        ];
    }
}
