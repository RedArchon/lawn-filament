<?php

namespace Tests\Feature;

use App\Enums\ServiceFrequency;
use App\Models\SeasonalFrequencyPeriod;
use App\Models\ServiceSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonalSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private function createSeasonalPeriodsFromFactory(ServiceSchedule $schedule, array $periods): void
    {
        foreach ($periods as $periodData) {
            SeasonalFrequencyPeriod::create(array_merge(
                ['service_schedule_id' => $schedule->id],
                $periodData
            ));
        }
    }

    public function test_manual_schedule_generates_single_appointment(): void
    {
        $schedule = ServiceSchedule::factory()->manual()->create([
            'start_date' => now()->addDays(5),
            'is_active' => true,
        ]);

        $appointments = $schedule->generateAppointments(now(), now()->addDays(30));

        $this->assertCount(1, $appointments);
        $this->assertEquals($schedule->start_date->toDateString(), $appointments->first()->scheduled_date->toDateString());
    }

    public function test_manual_schedule_outside_range_generates_no_appointments(): void
    {
        $schedule = ServiceSchedule::factory()->manual()->create([
            'start_date' => now()->addDays(40),
            'is_active' => true,
        ]);

        $appointments = $schedule->generateAppointments(now(), now()->addDays(30));

        $this->assertCount(0, $appointments);
    }

    public function test_recurring_weekly_schedule_generates_appointments(): void
    {
        $schedule = ServiceSchedule::factory()->recurring()->create([
            'start_date' => now(),
            'frequency' => 'weekly',
            'day_of_week' => now()->dayOfWeek,
            'is_active' => true,
        ]);

        $appointments = $schedule->generateAppointments(now(), now()->addDays(30));

        // Should generate approximately 4 weekly appointments
        $this->assertGreaterThanOrEqual(3, $appointments->count());
        $this->assertLessThanOrEqual(5, $appointments->count());
    }

    public function test_recurring_biweekly_schedule_generates_appointments(): void
    {
        $schedule = ServiceSchedule::factory()->recurring()->create([
            'start_date' => now(),
            'frequency' => 'biweekly',
            'day_of_week' => now()->dayOfWeek,
            'is_active' => true,
        ]);

        $appointments = $schedule->generateAppointments(now(), now()->addDays(30));

        // Should generate approximately 2 biweekly appointments
        $this->assertGreaterThanOrEqual(1, $appointments->count());
        $this->assertLessThanOrEqual(3, $appointments->count());
    }

    public function test_seasonal_schedule_generates_appointments_with_correct_frequency(): void
    {
        $schedule = ServiceSchedule::factory()->seasonal()->create([
            'start_date' => Carbon::create(2025, 1, 1),
            'is_active' => true,
        ]);

        // Create a simple seasonal period: Jan 1 - Jan 31, Every 7 days
        SeasonalFrequencyPeriod::factory()->create([
            'service_schedule_id' => $schedule->id,
            'start_month' => 1,
            'start_day' => 1,
            'end_month' => 1,
            'end_day' => 31,
            'frequency' => ServiceFrequency::Every7Days,
        ]);

        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::create(2025, 1, 31);

        $appointments = $schedule->generateAppointments($startDate, $endDate);

        // Should generate approximately 4-5 appointments (31 days / 7 days)
        $this->assertGreaterThanOrEqual(3, $appointments->count());
        $this->assertLessThanOrEqual(5, $appointments->count());
    }

    public function test_seasonal_period_contains_date_correctly(): void
    {
        $period = SeasonalFrequencyPeriod::factory()->create([
            'start_month' => 4,
            'start_day' => 1,
            'end_month' => 9,
            'end_day' => 30,
            'frequency' => ServiceFrequency::Every5Days,
        ]);

        $this->assertTrue($period->containsDate(Carbon::create(2025, 4, 1)));
        $this->assertTrue($period->containsDate(Carbon::create(2025, 6, 15)));
        $this->assertTrue($period->containsDate(Carbon::create(2025, 9, 30)));
        $this->assertFalse($period->containsDate(Carbon::create(2025, 3, 31)));
        $this->assertFalse($period->containsDate(Carbon::create(2025, 10, 1)));
    }

    public function test_seasonal_period_crossing_year_boundary(): void
    {
        // December 1 - January 31 period
        $period = SeasonalFrequencyPeriod::factory()->create([
            'start_month' => 12,
            'start_day' => 1,
            'end_month' => 1,
            'end_day' => 31,
            'frequency' => ServiceFrequency::Every3Weeks,
        ]);

        $this->assertTrue($period->containsDate(Carbon::create(2024, 12, 1)));
        $this->assertTrue($period->containsDate(Carbon::create(2024, 12, 15)));
        $this->assertTrue($period->containsDate(Carbon::create(2024, 12, 31)));
        $this->assertTrue($period->containsDate(Carbon::create(2025, 1, 1)));
        $this->assertTrue($period->containsDate(Carbon::create(2025, 1, 15)));
        $this->assertTrue($period->containsDate(Carbon::create(2025, 1, 31)));
        $this->assertFalse($period->containsDate(Carbon::create(2024, 11, 30)));
        $this->assertFalse($period->containsDate(Carbon::create(2025, 2, 1)));
    }

    public function test_seasonal_schedule_transitions_between_periods(): void
    {
        $schedule = ServiceSchedule::factory()->seasonal()->create([
            'start_date' => Carbon::create(2025, 2, 15),
            'is_active' => true,
        ]);

        // February - March: Weekly
        SeasonalFrequencyPeriod::factory()->create([
            'service_schedule_id' => $schedule->id,
            'start_month' => 2,
            'start_day' => 1,
            'end_month' => 3,
            'end_day' => 31,
            'frequency' => ServiceFrequency::Weekly,
        ]);

        // April - September: Every 5 days
        SeasonalFrequencyPeriod::factory()->create([
            'service_schedule_id' => $schedule->id,
            'start_month' => 4,
            'start_day' => 1,
            'end_month' => 9,
            'end_day' => 30,
            'frequency' => ServiceFrequency::Every5Days,
        ]);

        // Generate appointments across the transition
        $startDate = Carbon::create(2025, 2, 15);
        $endDate = Carbon::create(2025, 4, 30);

        $appointments = $schedule->generateAppointments($startDate, $endDate);

        // Verify appointments were generated
        $this->assertGreaterThan(5, $appointments->count());

        // Verify appointments exist in both periods
        $febMarchAppointments = $appointments->filter(fn ($apt) => $apt->scheduled_date->month <= 3);
        $aprilAppointments = $appointments->filter(fn ($apt) => $apt->scheduled_date->month === 4);

        $this->assertGreaterThan(0, $febMarchAppointments->count());
        $this->assertGreaterThan(0, $aprilAppointments->count());
    }

    public function test_brooksville_lawn_care_periods_cover_entire_year(): void
    {
        $schedule = ServiceSchedule::factory()->seasonal()->create([
            'start_date' => Carbon::create(2025, 1, 1),
            'is_active' => true,
        ]);

        // Add the 4 seasonal periods for Brooksville
        $this->createSeasonalPeriodsFromFactory(
            $schedule,
            \Database\Factories\SeasonalFrequencyPeriodFactory::brooksvilleLawnCarePeriods()
        );

        // Reload the schedule to get the periods
        $schedule->load('seasonalPeriods');

        // Test each month has a period
        foreach (range(1, 12) as $month) {
            $testDate = Carbon::create(2025, $month, 15);
            $period = $schedule->getCurrentSeasonalPeriod($testDate);

            $this->assertNotNull($period, "No period found for month {$month}");
        }
    }

    public function test_get_frequency_for_date_returns_correct_frequency(): void
    {
        $schedule = ServiceSchedule::factory()->seasonal()->create([
            'start_date' => Carbon::create(2025, 1, 1),
            'is_active' => true,
        ]);

        // April - September: Every 5 days
        SeasonalFrequencyPeriod::factory()->create([
            'service_schedule_id' => $schedule->id,
            'start_month' => 4,
            'start_day' => 1,
            'end_month' => 9,
            'end_day' => 30,
            'frequency' => ServiceFrequency::Every5Days,
        ]);

        $frequency = $schedule->getFrequencyForDate(Carbon::create(2025, 6, 15));

        $this->assertEquals(ServiceFrequency::Every5Days, $frequency);
    }

    public function test_year_over_year_repetition(): void
    {
        $schedule = ServiceSchedule::factory()->seasonal()->create([
            'start_date' => Carbon::create(2024, 1, 1),
            'is_active' => true,
        ]);

        // April - May: Weekly
        SeasonalFrequencyPeriod::factory()->create([
            'service_schedule_id' => $schedule->id,
            'start_month' => 4,
            'start_day' => 1,
            'end_month' => 5,
            'end_day' => 31,
            'frequency' => ServiceFrequency::Weekly,
        ]);

        // Generate for 2024
        $appointments2024 = $schedule->generateAppointments(
            Carbon::create(2024, 4, 1),
            Carbon::create(2024, 5, 31)
        );

        // Generate for 2025 (should use same periods)
        $appointments2025 = $schedule->generateAppointments(
            Carbon::create(2025, 4, 1),
            Carbon::create(2025, 5, 31)
        );

        // Both years should have appointments
        $this->assertGreaterThan(5, $appointments2024->count());
        $this->assertGreaterThan(5, $appointments2025->count());

        // Should be roughly the same number (within 1-2 due to day of week variations)
        $this->assertEqualsWithDelta(
            $appointments2024->count(),
            $appointments2025->count(),
            2
        );
    }

    public function test_inactive_schedule_generates_no_appointments(): void
    {
        $schedule = ServiceSchedule::factory()->seasonal()->create([
            'start_date' => now(),
            'is_active' => false,
        ]);

        // Add the 4 seasonal periods for Brooksville
        $this->createSeasonalPeriodsFromFactory(
            $schedule,
            \Database\Factories\SeasonalFrequencyPeriodFactory::brooksvilleLawnCarePeriods()
        );

        $appointments = $schedule->generateAppointments(now(), now()->addDays(90));

        $this->assertCount(0, $appointments);
    }

    public function test_schedule_with_end_date_stops_generating(): void
    {
        $schedule = ServiceSchedule::factory()->recurring()->create([
            'start_date' => now(),
            'end_date' => now()->addDays(15),
            'frequency' => 'weekly',
            'day_of_week' => now()->dayOfWeek,
            'is_active' => true,
        ]);

        $appointments = $schedule->generateAppointments(now(), now()->addDays(30));

        // All appointments should be before end date
        foreach ($appointments as $appointment) {
            $this->assertLessThanOrEqual(
                $schedule->end_date->timestamp,
                $appointment->scheduled_date->timestamp
            );
        }
    }
}
