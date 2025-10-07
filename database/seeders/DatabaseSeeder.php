<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user for Filament
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create 20 customers
        $customers = \App\Models\Customer::factory(20)->create();

        // Create 50-100 properties distributed across customers
        $propertyCount = fake()->numberBetween(50, 100);
        $properties = collect();

        foreach (range(1, $propertyCount) as $i) {
            $property = \App\Models\Property::factory()->create([
                'customer_id' => $customers->random()->id,
            ]);
            $properties->push($property);
        }

        // Create 30-50 notes (mix of customer notes and property notes)
        $noteCount = fake()->numberBetween(30, 50);

        foreach (range(1, $noteCount) as $i) {
            $notable = fake()->boolean(50)
                ? $customers->random()
                : $properties->random();

            \App\Models\Note::factory()->create([
                'notable_type' => get_class($notable),
                'notable_id' => $notable->id,
            ]);
        }

        // Create service types
        $serviceTypes = [
            ['name' => 'Mowing', 'duration' => 30, 'price' => 45.00, 'description' => 'Regular lawn mowing service'],
            ['name' => 'Edging', 'duration' => 15, 'price' => 20.00, 'description' => 'Edge trimming and cleanup'],
            ['name' => 'Fertilizing', 'duration' => 20, 'price' => 65.00, 'description' => 'Lawn fertilization treatment'],
            ['name' => 'Aeration', 'duration' => 45, 'price' => 85.00, 'description' => 'Core aeration service'],
            ['name' => 'Weed Control', 'duration' => 25, 'price' => 55.00, 'description' => 'Weed control and prevention'],
            ['name' => 'Leaf Removal', 'duration' => 60, 'price' => 95.00, 'description' => 'Seasonal leaf cleanup'],
        ];

        $createdServiceTypes = collect();
        foreach ($serviceTypes as $serviceType) {
            $createdServiceTypes->push(\App\Models\ServiceType::create([
                'name' => $serviceType['name'],
                'description' => $serviceType['description'],
                'default_duration_minutes' => $serviceType['duration'],
                'default_price' => $serviceType['price'],
                'is_active' => true,
            ]));
        }

        // Create 40 service schedules for random properties
        foreach (range(1, 40) as $i) {
            $frequency = fake()->randomElement(['weekly', 'biweekly', 'monthly']);

            \App\Models\ServiceSchedule::create([
                'property_id' => $properties->random()->id,
                'service_type_id' => $createdServiceTypes->random()->id,
                'frequency' => $frequency,
                'start_date' => fake()->dateTimeBetween('-1 month', 'now'),
                'end_date' => fake()->boolean(20) ? fake()->dateTimeBetween('+3 months', '+6 months') : null,
                'day_of_week' => in_array($frequency, ['weekly', 'biweekly']) ? fake()->numberBetween(1, 5) : null,
                'week_of_month' => $frequency === 'monthly' ? fake()->numberBetween(1, 4) : null,
                'is_active' => true,
                'notes' => fake()->optional(0.3)->sentence(),
            ]);
        }
    }
}
