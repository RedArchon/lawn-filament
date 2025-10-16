<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceType>
 */
class ServiceTypeFactory extends Factory
{
    public function definition(): array
    {
        $serviceTypes = [
            ['name' => 'Mowing', 'duration' => 30, 'price' => 45.00],
            ['name' => 'Edging', 'duration' => 15, 'price' => 20.00],
            ['name' => 'Fertilizing', 'duration' => 20, 'price' => 65.00],
            ['name' => 'Aeration', 'duration' => 45, 'price' => 85.00],
            ['name' => 'Weed Control', 'duration' => 25, 'price' => 55.00],
            ['name' => 'Leaf Removal', 'duration' => 60, 'price' => 95.00],
            ['name' => 'Trimming & Pruning', 'duration' => 40, 'price' => 70.00],
            ['name' => 'Mulching', 'duration' => 50, 'price' => 80.00],
        ];

        $service = fake()->randomElement($serviceTypes);

        return [
            'company_id' => Company::factory(),
            'name' => $service['name'],
            'description' => fake()->optional(0.7)->sentence(),
            'default_duration_minutes' => $service['duration'],
            'default_price' => $service['price'],
            'is_active' => fake()->boolean(90),
        ];
    }
}
