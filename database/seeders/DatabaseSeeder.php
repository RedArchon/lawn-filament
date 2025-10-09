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
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create additional users for team assignments
        $users = User::factory()->count(8)->create();
        $users->push($admin);

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

        // Get the Mowing service type for seasonal examples
        $mowingService = $createdServiceTypes->firstWhere('name', 'Mowing');

        // Create seasonal schedules (Brooksville, FL lawn mowing)
        foreach (range(1, 10) as $i) {
            $schedule = \App\Models\ServiceSchedule::factory()->seasonal()->create([
                'property_id' => $properties->random()->id,
                'service_type_id' => $mowingService->id,
                'start_date' => now()->subMonth(),
                'end_date' => null, // Ongoing
                'is_active' => true,
                'notes' => 'Brooksville, FL seasonal lawn mowing schedule',
            ]);

            // Add the 4 seasonal periods for Brooksville
            \App\Models\SeasonalFrequencyPeriod::factory()
                ->brooksvilleLawnCare()
                ->create(['service_schedule_id' => $schedule->id]);
        }

        // Create recurring schedules (simple recurring)
        foreach (range(1, 20) as $i) {
            \App\Models\ServiceSchedule::factory()->recurring()->create([
                'property_id' => $properties->random()->id,
                'service_type_id' => $createdServiceTypes->random()->id,
                'is_active' => true,
            ]);
        }

        // Create manual/one-off schedules
        foreach (range(1, 10) as $i) {
            \App\Models\ServiceSchedule::factory()->manual()->create([
                'property_id' => $properties->random()->id,
                'service_type_id' => $createdServiceTypes->random()->id,
                'start_date' => fake()->dateTimeBetween('now', '+1 month'),
                'is_active' => true,
                'notes' => 'One-time service',
            ]);
        }

        // Create teams
        $teams = collect([
            ['name' => 'Alpha Crew', 'color' => '#10b981', 'max_daily_appointments' => 15],
            ['name' => 'Bravo Team', 'color' => '#3b82f6', 'max_daily_appointments' => 12],
            ['name' => 'Charlie Squad', 'color' => '#f59e0b', 'max_daily_appointments' => 10],
            ['name' => 'Delta Crew', 'color' => '#ef4444', 'max_daily_appointments' => 18],
        ])->map(function ($teamData) {
            return \App\Models\Team::create([
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
