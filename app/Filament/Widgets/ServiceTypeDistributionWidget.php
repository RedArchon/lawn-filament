<?php

namespace App\Filament\Widgets;

use App\Models\ServiceAppointment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;

class ServiceTypeDistributionWidget extends ChartWidget
{
    public ?string $selectedDate = null;

    protected ?string $heading = 'Service Type Distribution';

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
            ->with('serviceType')
            ->get();

        if ($appointments->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $serviceTypeCounts = $appointments->countBy(fn ($apt) => $apt->serviceType?->name ?? 'Unknown');

        $labels = $serviceTypeCounts->keys()->toArray();
        $data = $serviceTypeCounts->values()->toArray();

        // Generate dynamic colors
        $colors = [
            'rgb(251, 191, 36)',  // Amber
            'rgb(34, 197, 94)',   // Green
            'rgb(59, 130, 246)',  // Blue
            'rgb(168, 85, 247)',  // Purple
            'rgb(236, 72, 153)',  // Pink
            'rgb(234, 179, 8)',   // Yellow
            'rgb(20, 184, 166)',  // Teal
            'rgb(249, 115, 22)',  // Orange
        ];

        $backgroundColor = [];
        for ($i = 0; $i < count($labels); $i++) {
            $backgroundColor[] = $colors[$i % count($colors)];
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
        return 'pie';
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
