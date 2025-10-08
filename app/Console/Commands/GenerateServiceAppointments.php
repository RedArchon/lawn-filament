<?php

namespace App\Console\Commands;

use App\Services\AppointmentGeneratorService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateServiceAppointments extends Command
{
    protected $signature = 'appointments:generate {--days=30 : Number of days to generate appointments for}';

    protected $description = 'Generate service appointments from active schedules';

    public function handle(AppointmentGeneratorService $generator): int
    {
        $days = (int) $this->option('days');
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($days);

        $this->info("Generating appointments from {$startDate->toDateString()} to {$endDate->toDateString()}...");

        $results = $generator->generateForAllSchedules($startDate, $endDate);

        $this->info("Processed {$results['total_schedules_processed']} schedule(s)");
        $this->info("Generated {$results['total_appointments_generated']} appointment(s)");

        if (! empty($results['details'])) {
            $this->table(
                ['Schedule ID', 'Property', 'Service Type', 'Appointments'],
                collect($results['details'])->map(fn ($detail) => [
                    $detail['schedule_id'],
                    $detail['property'],
                    $detail['service_type'],
                    $detail['appointments_generated'],
                ])->toArray()
            );
        }

        return Command::SUCCESS;
    }
}
