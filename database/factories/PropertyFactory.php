<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->postcode(),
            'latitude' => null,
            'longitude' => null,
            'geocoded_at' => null,
            'geocoding_failed' => false,
            'geocoding_error' => null,
            'lot_size' => fake()->randomElement(['1/4 acre', '1/2 acre', '1 acre', '2 acres', null]),
            'access_instructions' => fake()->boolean(30) ? fake()->sentence() : null,
            'service_status' => fake()->randomElement(['active', 'inactive', 'seasonal']),
        ];
    }
}
