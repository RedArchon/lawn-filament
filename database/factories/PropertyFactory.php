<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Customer;
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
        $customer = Customer::factory()->create();

        return [
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->numerify('#####'),
            'latitude' => null,
            'longitude' => null,
            'geocoded_at' => null,
            'geocoding_failed' => false,
            'geocoding_error' => null,
            'lot_size' => fake()->randomElement(['0.25 acres', '0.5 acres', '1 acre', '2 acres', null]),
            'access_instructions' => fake()->boolean(30) ? fake()->sentence() : null,
            'service_status' => fake()->randomElement(['active', 'inactive', 'seasonal']),
        ];
    }

    /**
     * Indicate that the property has been geocoded.
     */
    public function geocoded(): static
    {
        return $this->state(fn (array $attributes) => [
            'latitude' => fake()->latitude(25, 30),
            'longitude' => fake()->longitude(-85, -80),
            'geocoded_at' => now(),
            'geocoding_failed' => false,
            'geocoding_error' => null,
        ]);
    }
}
