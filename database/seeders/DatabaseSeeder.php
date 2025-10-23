<?php

namespace Database\Seeders;

use App\Models\Company;
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
        // Create 2-3 test companies
        $companies = Company::factory()->count(3)->create([
            'is_active' => true,
        ]);

        $primaryCompany = $companies->first();

        // Create admin user for primary company
        $admin = User::factory()->create([
            'company_id' => $primaryCompany->id,
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create additional users for team assignments in primary company
        $users = User::factory()->count(8)->create([
            'company_id' => $primaryCompany->id,
        ]);
        $users->push($admin);

        // Create 20 customers for primary company
        $customers = \App\Models\Customer::factory(20)->create([
            'company_id' => $primaryCompany->id,
        ]);

        // Create 50-100 properties distributed across customers
        // Use real Brooksville addresses for route optimization testing
        $propertyCount = fake()->numberBetween(50, 100);
        $properties = collect();
        $realAddresses = require database_path('seeders/data/test_addresses.php');

        \App\Models\Property::withoutEvents(function () use ($propertyCount, $customers, $realAddresses, $primaryCompany, &$properties) {
            foreach (range(1, $propertyCount) as $i) {
                $selectedAddress = fake()->randomElement($realAddresses);

                $property = \App\Models\Property::factory()->create([
                    'company_id' => $primaryCompany->id,
                    'customer_id' => $customers->random()->id,
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
                $properties->push($property);
            }
        });

        // Create 30-50 notes (mix of customer notes and property notes)
        $noteCount = fake()->numberBetween(30, 50);

        foreach (range(1, $noteCount) as $i) {
            $notable = fake()->boolean(50)
                ? $customers->random()
                : $properties->random();

            \App\Models\Note::factory()->create([
                'company_id' => $primaryCompany->id,
                'notable_type' => get_class($notable),
                'notable_id' => $notable->id,
            ]);
        }

        // Create service types for primary company
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
                'company_id' => $primaryCompany->id,
                'name' => $serviceType['name'],
                'description' => $serviceType['description'],
                'default_duration_minutes' => $serviceType['duration'],
                'default_price' => $serviceType['price'],
                'is_active' => true,
            ]));
        }

        // Get the Mowing service type for seasonal examples
        $mowingService = $createdServiceTypes->firstWhere('name', 'Mowing');

        // Create seasonal schedules (Brooksville, FL lawn mowing)
        foreach (range(1, 10) as $i) {
            $schedule = \App\Models\ServiceSchedule::factory()->seasonal()->create([
                'company_id' => $primaryCompany->id,
                'property_id' => $properties->random()->id,
                'service_type_id' => $mowingService->id,
                'start_date' => now()->subMonth(),
                'end_date' => null, // Ongoing
                'is_active' => true,
                'notes' => 'Brooksville, FL seasonal lawn mowing schedule',
            ]);

            // Add the 4 seasonal periods for Brooksville
            foreach (\Database\Factories\SeasonalFrequencyPeriodFactory::brooksvilleLawnCarePeriods() as $periodData) {
                \App\Models\SeasonalFrequencyPeriod::create(array_merge(
                    [
                        'company_id' => $primaryCompany->id,
                        'service_schedule_id' => $schedule->id,
                    ],
                    $periodData
                ));
            }
        }

        // Create recurring schedules (simple recurring)
        foreach (range(1, 20) as $i) {
            \App\Models\ServiceSchedule::factory()->recurring()->create([
                'company_id' => $primaryCompany->id,
                'property_id' => $properties->random()->id,
                'service_type_id' => $createdServiceTypes->random()->id,
                'is_active' => true,
            ]);
        }

        // Create manual/one-off schedules
        foreach (range(1, 10) as $i) {
            \App\Models\ServiceSchedule::factory()->manual()->create([
                'company_id' => $primaryCompany->id,
                'property_id' => $properties->random()->id,
                'service_type_id' => $createdServiceTypes->random()->id,
                'start_date' => fake()->dateTimeBetween('now', '+1 month'),
                'is_active' => true,
                'notes' => 'One-time service',
            ]);
        }

        // Create teams for primary company
        $teams = collect([
            ['name' => 'Alpha Crew', 'color' => '#10b981', 'max_daily_appointments' => 15],
            ['name' => 'Bravo Team', 'color' => '#3b82f6', 'max_daily_appointments' => 12],
            ['name' => 'Charlie Squad', 'color' => '#f59e0b', 'max_daily_appointments' => 10],
            ['name' => 'Delta Crew', 'color' => '#ef4444', 'max_daily_appointments' => 18],
        ])->map(function ($teamData) use ($primaryCompany) {
            return \App\Models\Team::create([
                'company_id' => $primaryCompany->id,
                'name' => $teamData['name'],
                'color' => $teamData['color'],
                'is_active' => true,
                'max_daily_appointments' => $teamData['max_daily_appointments'],
                'start_time' => '08:00:00',
            ]);
        });

        // Assign users to teams (2-3 users per team)
        $teams->each(function ($team) use ($users) {
            $teamUsers = $users->random(fake()->numberBetween(2, 3));
            $team->users()->attach($teamUsers->pluck('id'));
        });

        // Create service appointments for the next 7 days
        $tomorrow = now()->addDay();
        $createdCombinations = collect();

        foreach (range(0, 6) as $dayOffset) {
            $date = $tomorrow->copy()->addDays($dayOffset);
            $appointmentCount = fake()->numberBetween(15, 30);
            $attempts = 0;
            $maxAttempts = $appointmentCount * 3;

            for ($i = 0; $i < $appointmentCount && $attempts < $maxAttempts; $attempts++) {
                $property = $properties->random();
                $serviceType = $createdServiceTypes->random();

                // Check if this combination already exists
                $key = "{$property->id}-{$date->toDateString()}-{$serviceType->id}";
                if ($createdCombinations->contains($key)) {
                    continue;
                }

                $createdCombinations->push($key);

                // 70% of appointments get assigned to a team, 30% remain unassigned
                $team = fake()->boolean(70) ? $teams->random() : null;

                // For tomorrow's date, add some variety in statuses for demo purposes
                $status = 'scheduled';
                $completedAt = null;
                $completedBy = null;

                if ($dayOffset === 0 && $team) {
                    // 20% completed, 10% in progress, 70% scheduled
                    $rand = fake()->numberBetween(1, 100);
                    if ($rand <= 20) {
                        $status = 'completed';
                        $completedAt = $date->copy()->addHours(fake()->numberBetween(1, 8));
                        $completedBy = $users->random()->id;
                    } elseif ($rand <= 30) {
                        $status = 'in_progress';
                    }
                }

                \App\Models\ServiceAppointment::create([
                    'company_id' => $primaryCompany->id,
                    'property_id' => $property->id,
                    'service_type_id' => $serviceType->id,
                    'team_id' => $team?->id,
                    'scheduled_date' => $date,
                    'scheduled_time' => fake()->time('H:i:s', '16:00:00'),
                    'status' => $status,
                    'completed_at' => $completedAt,
                    'completed_by' => $completedBy,
                    'duration_minutes' => $serviceType->default_duration_minutes,
                ]);

                $i++;
            }
        }
    }
}
