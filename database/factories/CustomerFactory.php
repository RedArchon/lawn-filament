<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasCompany = fake()->boolean(30);
        $hasBillingAddress = fake()->boolean(60);

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+1 (###) ###-####'),
            'company_name' => $hasCompany ? fake()->company() : null,
            'billing_address' => $hasBillingAddress ? fake()->streetAddress() : null,
            'billing_city' => $hasBillingAddress ? fake()->city() : null,
            'billing_state' => $hasBillingAddress ? fake()->stateAbbr() : null,
            'billing_zip' => $hasBillingAddress ? fake()->numerify('#####') : null,
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
