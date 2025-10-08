<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316'];
        $crewNames = ['Alpha Crew', 'Bravo Team', 'Charlie Squad', 'Delta Crew', 'Echo Team', 'Foxtrot Squad'];

        return [
            'name' => fake()->randomElement($crewNames).' '.fake()->numberBetween(1, 20),
            'color' => fake()->randomElement($colors),
            'is_active' => fake()->boolean(90),
            'max_daily_appointments' => fake()->numberBetween(8, 20),
            'start_time' => fake()->time('H:i:s', '09:00:00'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
