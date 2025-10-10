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

    public function mount(?int $recordId = null): void
    {
        $this->recordId = $recordId;
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

        // Stop polling after 60 seconds
        if ($this->geocodingStartTime && (time() - $this->geocodingStartTime) > 60) {
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

        // Reload the record from database to get latest geocoding status
        $freshRecord = Property::find($this->recordId);

        return $freshRecord
            && $freshRecord->latitude
            && $freshRecord->longitude
            && ! $freshRecord->geocoding_failed;
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

        // Reload the record from database to get latest geocoding status
        $freshRecord = Property::find($this->recordId);

        return $freshRecord && $freshRecord->geocoding_failed;
    }

    public function getPropertyAddress(): string
    {
        if (! $this->recordId) {
            return 'Unknown Address';
        }

        $freshRecord = Property::find($this->recordId);

        return $freshRecord ? $freshRecord->full_address : 'Unknown Address';
    }

    public function getProperty(): ?Property
    {
        if (! $this->recordId) {
            return null;
        }

        return Property::find($this->recordId);
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

                $freshRecord = Property::find($this->recordId);

                return "Do you want to geocode this property?\n\nAddress: ".($freshRecord ? $freshRecord->full_address : 'Unknown');
            })
            ->modalSubmitActionLabel('Yes, Geocode')
            ->action(function () {
                if (! $this->recordId) {
                    return;
                }

                $freshRecord = Property::find($this->recordId);
                if (! $freshRecord) {
                    return;
                }

                GeocodePropertyJob::dispatch($freshRecord);

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
