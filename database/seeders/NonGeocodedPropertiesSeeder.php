<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Property;
use Illuminate\Database\Seeder;

class NonGeocodedPropertiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get random customers to assign properties to
        $customers = Customer::inRandomOrder()->limit(10)->get();

        if ($customers->isEmpty()) {
            $this->command->warn('No customers found. Creating customers first...');
            $customers = Customer::factory()->count(10)->create();
        }

        // Real addresses in Brooksville, Port Richey, and Hudson, FL that need geocoding
        $addresses = [
            // Brooksville addresses
            ['address' => '201 Howell Avenue', 'city' => 'Brooksville', 'state' => 'FL', 'zip' => '34601'],
            ['address' => '122 E Liberty Street', 'city' => 'Brooksville', 'state' => 'FL', 'zip' => '34601'],
            ['address' => '30 S Main Street', 'city' => 'Brooksville', 'state' => 'FL', 'zip' => '34601'],
            ['address' => '15340 Cortez Boulevard', 'city' => 'Brooksville', 'state' => 'FL', 'zip' => '34613'],
            ['address' => '7550 Forest Oaks Boulevard', 'city' => 'Brooksville', 'state' => 'FL', 'zip' => '34601'],
            // Port Richey addresses
            ['address' => '6333 Ridge Road', 'city' => 'Port Richey', 'state' => 'FL', 'zip' => '34668'],
            ['address' => '7530 Main Street', 'city' => 'Port Richey', 'state' => 'FL', 'zip' => '34668'],
            ['address' => '10330 US Highway 19', 'city' => 'Port Richey', 'state' => 'FL', 'zip' => '34668'],
            ['address' => '8831 Old County Road 54', 'city' => 'Port Richey', 'state' => 'FL', 'zip' => '34668'],
            ['address' => '11115 US Highway 19 N', 'city' => 'Port Richey', 'state' => 'FL', 'zip' => '34668'],
            // Hudson addresses
            ['address' => '14139 Hicks Road', 'city' => 'Hudson', 'state' => 'FL', 'zip' => '34667'],
            ['address' => '11650 US Highway 19', 'city' => 'Hudson', 'state' => 'FL', 'zip' => '34667'],
            ['address' => '7432 Ridge Road', 'city' => 'Hudson', 'state' => 'FL', 'zip' => '34667'],
            ['address' => '13050 Little Road', 'city' => 'Hudson', 'state' => 'FL', 'zip' => '34667'],
            ['address' => '16420 US Highway 19 N', 'city' => 'Hudson', 'state' => 'FL', 'zip' => '34667'],
        ];

        $this->command->info('Creating 15 non-geocoded properties...');

        foreach ($addresses as $address) {
            Property::create([
                'customer_id' => $customers->random()->id,
                'address' => $address['address'],
                'city' => $address['city'],
                'state' => $address['state'],
                'zip' => $address['zip'],
                'latitude' => null,
                'longitude' => null,
                'geocoded_at' => null,
                'geocoding_failed' => false,
                'geocoding_error' => null,
                'lot_size' => fake()->randomElement(['0.25 acres', '0.5 acres', '1 acre', '2 acres']),
                'access_instructions' => fake()->boolean(30) ? fake()->sentence() : null,
                'service_status' => fake()->randomElement(['active', 'inactive', 'seasonal']),
            ]);
        }

        $this->command->info('Successfully created 15 non-geocoded properties!');
        $this->command->info('These properties can now be geocoded using the PropertyMapWidget.');
    }
}
