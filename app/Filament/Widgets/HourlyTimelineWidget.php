<?php

namespace App\Filament\Widgets;

use App\Models\ServiceAppointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class HourlyTimelineWidget extends ChartWidget
{
    public ?string $selectedDate = null;

    protected ?string $heading = 'Hourly Appointment Distribution';

    protected ?string $maxHeight = '300px';

    protected int|string|array $columnSpan = 1;

    #[On('dateChanged')]
    public function updateDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->updateChartData();
    }

    protected function getData(): array
    {
        if (! $this->selectedDate) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $appointments = ServiceAppointment::query()
            ->forDate(Carbon::parse($this->selectedDate))
            ->get();

        if ($appointments->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        // Initialize hourly counts from 6 AM to 8 PM
        $hourlyCounts = [];
        for ($hour = 6; $hour <= 20; $hour++) {
            $hourlyCounts[$hour] = 0;
        }

        // Count appointments by scheduled hour
        foreach ($appointments as $appointment) {
            if ($appointment->scheduled_at) {
                $hour = Carbon::parse($appointment->scheduled_at)->hour;
                if ($hour >= 6 && $hour <= 20) {
                    $hourlyCounts[$hour]++;
                }
            }
        }

        $labels = [];
        $data = [];

        foreach ($hourlyCounts as $hour => $count) {
            $labels[] = Carbon::createFromTime($hour)->format('g A');
            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Appointments',
                    'data' => $data,
                    'backgroundColor' => 'rgb(251, 191, 36)', // Amber
                    'borderColor' => 'rgb(245, 158, 11)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
            'maintainAspectRatio' => true,
        ];
    }
}
