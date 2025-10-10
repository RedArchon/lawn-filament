<?php

namespace App\Filament\Widgets;

use App\Jobs\GeocodePropertyJob;
use App\Models\Property;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Widgets\Widget;

class PropertyMapWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.widgets.property-map';

    protected int|string|array $columnSpan = 'full';

    public ?int $recordId = null;

    public bool $isGeocoding = false;

    public ?int $geocodingStartTime = null;

    protected $listeners = ['refresh' => '$refresh'];

    // Configuration constants
    private const GEOCODING_TIMEOUT_SECONDS = 60;

    // Property caching to avoid multiple database queries
    private ?Property $cachedProperty = null;

    private ?int $lastPropertyCheck = null;

    public function mount(?int $recordId = null): void
    {
        $this->recordId = $recordId;
    }

    /**
     * Get the property with caching to avoid multiple database queries.
     * Cache expires after 5 seconds to ensure we get fresh data during geocoding.
     */
    private function getCachedProperty(): ?Property
    {
        $now = time();

        // If we have a cached property and it's been less than 5 seconds, return it
        if ($this->cachedProperty && $this->lastPropertyCheck && ($now - $this->lastPropertyCheck) < 5) {
            return $this->cachedProperty;
        }

        // Fetch fresh property and cache it
        if ($this->recordId) {
            $this->cachedProperty = Property::find($this->recordId);
            $this->lastPropertyCheck = $now;
        }

        return $this->cachedProperty;
    }

    public function checkGeocodingStatus(): void
    {
        if (! $this->isGeocoding) {
            return;
        }

        // Check if geocoding is complete
        if ($this->isGeocoded()) {
            $this->isGeocoding = false;
            $this->geocodingStartTime = null;

            Notification::make()
                ->title('Geocoding Complete')
                ->body('The property has been successfully geocoded.')
                ->success()
                ->send();

            return;
        }

        // Stop polling after timeout
        if ($this->geocodingStartTime && (time() - $this->geocodingStartTime) > self::GEOCODING_TIMEOUT_SECONDS) {
            $this->isGeocoding = false;

            Notification::make()
                ->title('Geocoding Timeout')
                ->body('Geocoding is taking longer than expected. Please check the job queue or try again.')
                ->warning()
                ->send();
        }
    }

    public function isGeocoded(): bool
    {
        if (! $this->recordId) {
            return false;
        }

        $property = $this->getCachedProperty();

        return $property
            && $property->latitude
            && $property->longitude
            && ! $property->geocoding_failed;
    }

    public function needsGeocoding(): bool
    {
        return ! $this->isGeocoded();
    }

    public function geocodingFailed(): bool
    {
        if (! $this->recordId) {
            return false;
        }

        $property = $this->getCachedProperty();

        return $property && $property->geocoding_failed;
    }

    public function getPropertyAddress(): string
    {
        if (! $this->recordId) {
            return 'Unknown Address';
        }

        $property = $this->getCachedProperty();

        return $property ? $property->full_address : 'Unknown Address';
    }

    public function getProperty(): ?Property
    {
        if (! $this->recordId) {
            return null;
        }

        return $this->getCachedProperty();
    }

    public function geocodeAction(): Action
    {
        return Action::make('geocode')
            ->label('Geocode Property')
            ->icon('heroicon-o-map-pin')
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Geocode Property')
            ->modalDescription(function () {
                if (! $this->recordId) {
                    return "Do you want to geocode this property?\n\nAddress: Unknown";
                }

                $property = $this->getCachedProperty();

                return "Do you want to geocode this property?\n\nAddress: ".($property ? $property->full_address : 'Unknown');
            })
            ->modalSubmitActionLabel('Yes, Geocode')
            ->action(function () {
                if (! $this->recordId) {
                    return;
                }

                $property = $this->getCachedProperty();
                if (! $property) {
                    return;
                }

                GeocodePropertyJob::dispatch($property);

                $this->isGeocoding = true;
                $this->geocodingStartTime = time();

                Notification::make()
                    ->title('Geocoding Started')
                    ->body('The property is being geocoded. This may take a few moments.')
                    ->success()
                    ->send();
            });
    }
}
