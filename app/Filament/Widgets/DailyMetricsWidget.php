<?php

namespace App\Filament\Widgets;

use App\Models\ServiceAppointment;
use Carbon\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class DailyMetricsWidget extends StatsOverviewWidget
{
    public ?string $selectedDate = null;

    protected int|string|array $columnSpan = 'full';

    #[On('dateChanged')]
    public function updateDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    protected function getStats(): array
    {
        if (! $this->selectedDate) {
            return [];
        }

        $appointments = ServiceAppointment::query()
            ->forDate(Carbon::parse($this->selectedDate))
            ->with(['property', 'serviceType'])
            ->get();

        $total = $appointments->count();
        $geocoded = $appointments->filter(function ($appointment) {
            return $appointment->property->latitude
                && $appointment->property->longitude
                && ! $appointment->property->geocoding_failed;
        })->count();
        $needGeocoding = $total - $geocoded;
        $totalMinutes = $appointments->sum('duration_minutes');

        return [
            Stat::make('Total Appointments', $total)
                ->description('Scheduled for this day')
                ->descriptionIcon('heroicon-o-calendar', IconPosition::Before)
                ->color('primary'),

            Stat::make('Ready for Routing', $geocoded)
                ->description('Geocoded properties')
                ->descriptionIcon('heroicon-o-map', IconPosition::Before)
                ->color('success'),

            Stat::make('Need Geocoding', $needGeocoding)
                ->description('Missing coordinates')
                ->descriptionIcon('heroicon-o-map-pin', IconPosition::Before)
                ->color($needGeocoding > 0 ? 'warning' : 'success'),

            Stat::make('Total Duration', $totalMinutes.' min')
                ->description('Estimated time')
                ->descriptionIcon('heroicon-o-clock', IconPosition::Before)
                ->color('info'),
        ];
    }
}
