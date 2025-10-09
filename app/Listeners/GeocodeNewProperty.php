<?php

namespace App\Listeners;

use App\Events\PropertyCreated;
use App\Jobs\GeocodePropertyJob;
use Illuminate\Support\Facades\Log;

class GeocodeNewProperty
{
    /**
     * Handle the event.
     */
    public function handle(PropertyCreated $event): void
    {
        if (! config('services.google.geocoding_enabled')) {
            Log::info('Geocoding skipped for property', [
                'property_id' => $event->property->id,
                'reason' => 'Geocoding is disabled in configuration',
            ]);

            return;
        }

        GeocodePropertyJob::dispatch($event->property);

        Log::info('Geocoding job dispatched for property', [
            'property_id' => $event->property->id,
        ]);
    }
}
