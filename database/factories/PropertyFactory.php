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

    /**
     * Real addresses for testing (without geocoding).
     * Use this when you want the geocoding to happen naturally via events.
     */
    public function testAddresses(): static
    {
        $addresses = require database_path('seeders/data/test_addresses.php');
        $selectedAddress = fake()->randomElement($addresses);

        return $this->state(fn (array $attributes) => [
            'address' => $selectedAddress['address'],
            'city' => $selectedAddress['city'],
            'state' => $selectedAddress['state'],
            'zip' => $selectedAddress['zip'],
            'latitude' => null,
            'longitude' => null,
            'geocoded_at' => null,
            'geocoding_failed' => false,
            'geocoding_error' => null,
        ]);
    }

    /**
     * Real addresses for testing with pre-geocoded coordinates.
     * Use this for faster seeding without API calls.
     */
    public function testAddressesGeocoded(): static
    {
        $addresses = require database_path('seeders/data/test_addresses.php');
        $selectedAddress = fake()->randomElement($addresses);

        return $this->state(fn (array $attributes) => [
            'address' => $selectedAddress['address'],
            'city' => $selectedAddress['city'],
            'state' => $selectedAddress['state'],
            'zip' => $selectedAddress['zip'],
            'latitude' => $selectedAddress['lat'],
            'longitude' => $selectedAddress['lon'],
            'geocoded_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'geocoding_failed' => false,
            'geocoding_error' => null,
        ]);
    }
}
