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
        return [];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (\App\Models\Property $property) {
            // Only create customer if not provided
            if (! $property->customer_id) {
                // If company_id is set, create customer with same company
                if ($property->company_id) {
                    $customer = Customer::factory()->create(['company_id' => $property->company_id]);
                    $property->customer_id = $customer->id;
                } else {
                    // Create fresh customer with its own company
                    $customer = Customer::factory()->create();
                    $property->company_id = $customer->company_id;
                    $property->customer_id = $customer->id;
                }
            } elseif (! $property->company_id) {
                // Customer provided but no company - get company from customer
                $customer = Customer::find($property->customer_id);
                $property->company_id = $customer->company_id;
            }

            // Set other fields if not already set
            if (! $property->address) {
                $property->address = fake()->streetAddress();
            }
            if (! $property->city) {
                $property->city = fake()->city();
            }
            if (! $property->state) {
                $property->state = fake()->stateAbbr();
            }
            if (! $property->zip) {
                $property->zip = fake()->numerify('#####');
            }
            if (! isset($property->latitude)) {
                $property->latitude = null;
            }
            if (! isset($property->longitude)) {
                $property->longitude = null;
            }
            if (! $property->geocoded_at) {
                $property->geocoded_at = null;
            }
            if (! isset($property->geocoding_failed)) {
                $property->geocoding_failed = false;
            }
            if (! $property->geocoding_error) {
                $property->geocoding_error = null;
            }
            if (! $property->lot_size) {
                $property->lot_size = fake()->randomElement(['0.25 acres', '0.5 acres', '1 acre', '2 acres', null]);
            }
            if (! $property->access_instructions) {
                $property->access_instructions = fake()->boolean(30) ? fake()->sentence() : null;
            }
            if (! $property->service_status) {
                $property->service_status = fake()->randomElement(['active', 'inactive', 'seasonal']);
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
