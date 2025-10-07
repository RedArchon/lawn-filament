<?php

namespace App\Filament\Widgets;

use App\Models\ServiceAppointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class AppointmentStatusChartWidget extends ChartWidget
{
    public ?string $selectedDate = null;

    protected ?string $heading = 'Appointment Status Distribution';

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

        $statusCounts = $appointments->countBy('status');

        $statusLabels = [
            'scheduled' => 'Scheduled',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'skipped' => 'Skipped',
        ];

        $statusColors = [
            'scheduled' => 'rgb(251, 191, 36)', // Amber
            'in_progress' => 'rgb(59, 130, 246)', // Blue
            'completed' => 'rgb(34, 197, 94)', // Green
            'cancelled' => 'rgb(239, 68, 68)', // Red
            'skipped' => 'rgb(156, 163, 175)', // Gray
        ];

        $labels = [];
        $data = [];
        $backgroundColor = [];

        foreach ($statusCounts as $status => $count) {
            $labels[] = $statusLabels[$status] ?? ucfirst($status);
            $data[] = $count;
            $backgroundColor[] = $statusColors[$status] ?? 'rgb(156, 163, 175)';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Appointments',
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => true,
        ];
    }
}
