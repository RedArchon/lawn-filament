<?php

namespace App\Services;

use App\Models\ServiceSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AppointmentGeneratorService
{
    public function generateForSchedule(ServiceSchedule $schedule, Carbon $startDate, Carbon $endDate): Collection
    {
        if (! $schedule->is_active) {
            return collect();
        }

        if ($schedule->end_date && $schedule->end_date->lt($startDate)) {
            return collect();
        }

        return $schedule->generateAppointments($startDate, $endDate);
    }

    public function generateForAllSchedules(Carbon $startDate, Carbon $endDate): array
    {
        $schedules = ServiceSchedule::query()
            ->dueForGeneration()
            ->with(['property', 'serviceType', 'seasonalPeriods'])
            ->get();

        $totalGenerated = 0;
        $results = [];

        foreach ($schedules as $schedule) {
            $appointments = $this->generateForSchedule($schedule, $startDate, $endDate);
            $count = $appointments->count();

            if ($count > 0) {
                $totalGenerated += $count;
                $results[] = [
                    'schedule_id' => $schedule->id,
                    'property' => $schedule->property->full_address,
                    'service_type' => $schedule->serviceType->name,
                    'appointments_generated' => $count,
                ];

                Log::info("Generated {$count} appointment(s) for schedule {$schedule->id}");
            }
        }

        return [
            'total_schedules_processed' => $schedules->count(),
            'total_appointments_generated' => $totalGenerated,
            'details' => $results,
        ];
    }
}
