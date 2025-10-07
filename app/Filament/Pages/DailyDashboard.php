<?php

namespace App\Filament\Pages;

use App\Models\ServiceAppointment;
use App\Services\RouteOptimizationService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class DailyDashboard extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Daily Dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.daily-dashboard';

    public ?array $data = [];

    public ?string $selectedDate = null;

    public ?Collection $appointments = null;

    public ?array $optimizedRoute = null;

    public function mount(): void
    {
        $this->selectedDate = now()->addDay()->toDateString();
        $this->form->fill(['date' => $this->selectedDate]);
        $this->loadAppointments();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                DatePicker::make('date')
                    ->label('Select Date')
                    ->native(false)
                    ->displayFormat('M d, Y')
                    ->default(now()->addDay())
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedDate = $state;
                        $this->loadAppointments();
                        $this->optimizedRoute = null;
                    }),
            ])
            ->statePath('data');
    }

    public function loadAppointments(): void
    {
        if (! $this->selectedDate) {
            $this->appointments = collect();

            return;
        }

        $this->appointments = ServiceAppointment::query()
            ->forDate(Carbon::parse($this->selectedDate))
            ->with(['property.customer', 'serviceType'])
            ->orderBy('scheduled_time')
            ->orderBy('id')
            ->get();
    }

    public function optimizeRoute(): void
    {
        if (! $this->selectedDate) {
            Notification::make()
                ->danger()
                ->title('Validation Error')
                ->body('Please select a date.')
                ->send();

            return;
        }

        $readyCount = ServiceAppointment::query()
            ->forDate(Carbon::parse($this->selectedDate))
            ->readyForRouting()
            ->count();

        if ($readyCount === 0) {
            Notification::make()
                ->warning()
                ->title('No Appointments Ready')
                ->body('There are no appointments with geocoded properties for this date.')
                ->send();

            return;
        }

        try {
            $service = app(RouteOptimizationService::class);
            $result = $service->optimizeForDate(Carbon::parse($this->selectedDate));

            $this->optimizedRoute = [
                'properties' => $result['optimized_order'],
                'total_distance_miles' => round($result['total_distance_meters'] / 1609.34, 2),
                'total_duration_minutes' => round($result['total_duration_seconds'] / 60, 0),
                'appointment_count' => $result['appointment_count'],
                'appointments' => $result['appointments'],
            ];

            Notification::make()
                ->success()
                ->title('Route Optimized!')
                ->body("Optimized {$this->optimizedRoute['appointment_count']} appointments.")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Optimization Failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('optimizeRoute')
                ->label('Optimize Route')
                ->icon('heroicon-o-map')
                ->color('primary')
                ->disabled(fn () => $this->appointments?->isEmpty() ?? true)
                ->action('optimizeRoute'),
        ];
    }

    public function getAppointmentStats(): array
    {
        if (! $this->appointments) {
            return [
                'total' => 0,
                'geocoded' => 0,
                'not_geocoded' => 0,
                'total_duration' => 0,
            ];
        }

        $geocoded = $this->appointments->filter(function ($appointment) {
            return $appointment->property->latitude
                && $appointment->property->longitude
                && ! $appointment->property->geocoding_failed;
        });

        return [
            'total' => $this->appointments->count(),
            'geocoded' => $geocoded->count(),
            'not_geocoded' => $this->appointments->count() - $geocoded->count(),
            'total_duration' => $this->appointments->sum('duration_minutes') ?? 0,
        ];
    }
}
