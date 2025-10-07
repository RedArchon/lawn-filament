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
    }
}
