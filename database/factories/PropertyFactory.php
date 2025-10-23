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
        return [
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

    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\Property $property) {
            // Handle company/customer relationship
            if ($property->customer_id) {
                // Customer provided - inherit their company if not set
                if (! $property->company_id) {
                    $property->company_id = Customer::find($property->customer_id)->company_id;
                }
            } else {
                // No customer - create one with matching company
                $customer = Customer::factory()->create(
                    $property->company_id ? ['company_id' => $property->company_id] : []
                );
                $property->company_id = $customer->company_id;
                $property->customer_id = $customer->id;
            }
        });
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
