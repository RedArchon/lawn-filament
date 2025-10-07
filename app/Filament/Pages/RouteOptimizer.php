<?php

namespace App\Filament\Pages;

use App\Models\Property;
use App\Services\RouteOptimizationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

class RouteOptimizer extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Route Optimizer';

    protected string $view = 'filament.pages.route-optimizer';

    public ?array $data = [];

    public ?array $optimizedRoute = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('property_ids')
                    ->label('Select Properties')
                    ->multiple()
                    ->options(
                        Property::query()
                            ->geocoded()
                            ->active()
                            ->with('customer')
                            ->get()
                            ->mapWithKeys(fn ($property) => [
                                $property->id => "{$property->full_address} ({$property->customer->name})",
                            ])
                    )
                    ->required()
                    ->minItems(2)
                    ->searchable()
                    ->helperText('Select at least 2 properties to optimize the route'),

                TextInput::make('start_location')
                    ->label('Starting Location (Optional)')
                    ->helperText('Leave blank to start from the first property')
                    ->placeholder('123 Main St, City, State ZIP'),
            ])
            ->statePath('data');
    }

    public function optimize(): void
    {
        $data = $this->form->getState();

        if (empty($data['property_ids']) || count($data['property_ids']) < 2) {
            Notification::make()
                ->danger()
                ->title('Validation Error')
                ->body('Please select at least 2 properties.')
                ->send();

            return;
        }

        try {
            $properties = Property::query()
                ->whereIn('id', $data['property_ids'])
                ->with('customer')
                ->get();

            $service = app(RouteOptimizationService::class);
            $result = $service->optimize($properties, $data['start_location'] ?? null);

            $this->optimizedRoute = [
                'properties' => $result['optimized_order'],
                'total_distance_miles' => round($result['total_distance_meters'] / 1609.34, 2),
                'total_duration_minutes' => round($result['total_duration_seconds'] / 60, 0),
            ];

            Notification::make()
                ->success()
                ->title('Route Optimized!')
                ->body('Your route has been optimized successfully.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Optimization Failed')
                ->body($e->getMessage())
                ->send();
        }
    }
}
