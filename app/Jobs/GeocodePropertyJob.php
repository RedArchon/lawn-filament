<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\GeocodeService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class GeocodePropertyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public Property $property) {}

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping($this->property->id)];
    }

    /**
     * Execute the job.
     */
    public function handle(GeocodeService $geocodeService): void
    {
        try {
            $result = $geocodeService->geocode($this->property->full_address);

            $this->property->update([
                'latitude' => $result['lat'],
                'longitude' => $result['lng'],
                'geocoded_at' => now(),
                'geocoding_failed' => false,
                'geocoding_error' => null,
            ]);

            Log::info("Property {$this->property->id} geocoded successfully", [
                'property_id' => $this->property->id,
                'lat' => $result['lat'],
                'lng' => $result['lng'],
            ]);
        } catch (Exception $e) {
            $this->property->update([
                'geocoding_failed' => true,
                'geocoding_error' => $e->getMessage(),
            ]);

            Log::error("Failed to geocode property {$this->property->id}", [
                'property_id' => $this->property->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
