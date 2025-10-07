<?php

namespace App\Observers;

use App\Jobs\GeocodePropertyJob;
use App\Models\Property;

class PropertyObserver
{
    /**
     * Handle the Property "updating" event.
     */
    public function updating(Property $property): void
    {
        $addressFields = ['address', 'city', 'state', 'zip'];
        $addressChanged = false;

        foreach ($addressFields as $field) {
            if ($property->isDirty($field)) {
                $addressChanged = true;
                break;
            }
        }

        if ($addressChanged) {
            $property->latitude = null;
            $property->longitude = null;
            $property->geocoded_at = null;
            $property->geocoding_failed = false;
            $property->geocoding_error = null;
        }
    }

    /**
     * Handle the Property "updated" event.
     */
    public function updated(Property $property): void
    {
        $addressFields = ['address', 'city', 'state', 'zip'];

        foreach ($addressFields as $field) {
            if ($property->wasChanged($field)) {
                GeocodePropertyJob::dispatch($property);
                break;
            }
        }
    }
}
