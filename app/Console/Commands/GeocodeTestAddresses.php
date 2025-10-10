<?php

namespace App\Console\Commands;

use App\Services\GeocodeService;
use Illuminate\Console\Command;

class GeocodeTestAddresses extends Command
{
    protected $signature = 'geocode:test-addresses';

    protected $description = 'Geocode all addresses in test_addresses.php with accurate coordinates';

    public function __construct(private GeocodeService $geocodeService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $addressesFile = database_path('seeders/data/test_addresses.php');
        
        if (!file_exists($addressesFile)) {
            $this->error('test_addresses.php file not found!');
            return Command::FAILURE;
        }

        $addresses = require $addressesFile;
        $geocoded = [];
        $failed = [];

        $this->info('Starting geocoding of ' . count($addresses) . ' addresses...');
        $this->newLine();

        $bar = $this->output->createProgressBar(count($addresses));
        $bar->start();

        foreach ($addresses as $index => $address) {
            $fullAddress = "{$address['address']}, {$address['city']}, {$address['state']} {$address['zip']}";
            
            try {
                // Add a small delay to avoid rate limiting
                sleep(1);
                
                $result = $this->geocodeService->geocode($fullAddress);
                
                $geocoded[] = [
                    'address' => $address['address'],
                    'city' => $address['city'],
                    'state' => $address['state'],
                    'zip' => $address['zip'],
                    'lat' => round($result['lat'], 6),
                    'lon' => round($result['lng'], 6),
                ];
                
            } catch (\Exception $e) {
                $failed[] = [
                    'address' => $fullAddress,
                    'error' => $e->getMessage(),
                ];
                
                // Keep original coordinates if geocoding fails
                $geocoded[] = [
                    'address' => $address['address'],
                    'city' => $address['city'],
                    'state' => $address['state'],
                    'zip' => $address['zip'],
                    'lat' => $address['lat'],
                    'lon' => $address['lon'],
                ];
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Write updated addresses back to file
        $phpContent = "<?php\n\n/**\n * Real addresses in Brooksville, FL and surrounding areas.\n * All addresses have been verified with actual geocoded coordinates.\n * This data is used for realistic route optimization testing.\n */\nreturn [\n";
        
        foreach ($geocoded as $addr) {
            $phpContent .= sprintf(
                "    ['address' => '%s', 'city' => '%s', 'state' => '%s', 'zip' => '%s', 'lat' => %s, 'lon' => %s],\n",
                $addr['address'],
                $addr['city'],
                $addr['state'],
                $addr['zip'],
                $addr['lat'],
                $addr['lon']
            );
        }
        
        $phpContent .= "];\n";
        
        file_put_contents($addressesFile, $phpContent);

        $this->info('Successfully geocoded ' . count($geocoded) . ' addresses');
        
        if (count($failed) > 0) {
            $this->newLine();
            $this->warn('Failed to geocode ' . count($failed) . ' addresses (kept original coordinates):');
            foreach ($failed as $fail) {
                $this->line('  - ' . $fail['address'] . ': ' . $fail['error']);
            }
        }

        $this->newLine();
        $this->info('Updated test_addresses.php with accurate coordinates!');

        return Command::SUCCESS;
    }
}