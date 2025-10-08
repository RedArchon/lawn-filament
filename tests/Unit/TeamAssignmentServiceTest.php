<?php

namespace Tests\Unit;

use App\Models\Property;
use App\Models\ServiceAppointment;
use App\Models\Team;
use App\Services\TeamAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TeamAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TeamAssignmentService;
    }

    public function test_auto_assigns_appointments_to_active_teams(): void
    {
        $team1 = Team::factory()->create(['is_active' => true, 'max_daily_appointments' => 10]);
        $team2 = Team::factory()->create(['is_active' => true, 'max_daily_appointments' => 10]);

        $date = now()->addDay();

        $appointments = ServiceAppointment::factory()->count(5)->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'scheduled',
        ]);

        $result = $this->service->autoAssignAppointments($date);

        $this->assertEquals(5, $result['total_assigned']);
        $this->assertEquals(5, $result['total_unassigned']);
        $this->assertEquals(0, $result['remaining_unassigned']);

        foreach ($appointments as $appointment) {
            $this->assertNotNull($appointment->fresh()->team_id);
        }
    }

    public function test_respects_team_max_daily_appointments(): void
    {
        $team = Team::factory()->create(['is_active' => true, 'max_daily_appointments' => 2]);

        $date = now()->addDay();

        ServiceAppointment::factory()->count(5)->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'scheduled',
        ]);

        $result = $this->service->autoAssignAppointments($date);

        // Should only assign 2 appointments (team's max capacity)
        $this->assertEquals(2, $result['total_assigned']);
        $this->assertEquals(3, $result['remaining_unassigned']);
    }

    public function test_distributes_appointments_across_multiple_teams(): void
    {
        $team1 = Team::factory()->create(['is_active' => true, 'max_daily_appointments' => 3]);
        $team2 = Team::factory()->create(['is_active' => true, 'max_daily_appointments' => 3]);

        $date = now()->addDay();

        ServiceAppointment::factory()->count(6)->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'scheduled',
        ]);

        $result = $this->service->autoAssignAppointments($date);

        $this->assertEquals(6, $result['total_assigned']);
        $this->assertEquals(2, $result['teams_used']);
    }

    public function test_only_assigns_scheduled_appointments(): void
    {
        $team = Team::factory()->create(['is_active' => true, 'max_daily_appointments' => 10]);

        $date = now()->addDay();

        ServiceAppointment::factory()->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'scheduled',
        ]);

        ServiceAppointment::factory()->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'completed',
        ]);

        $result = $this->service->autoAssignAppointments($date);

        $this->assertEquals(1, $result['total_assigned']);
    }

    public function test_throws_exception_when_no_active_teams_available(): void
    {
        Team::factory()->create(['is_active' => false]);

        $date = now()->addDay();

        ServiceAppointment::factory()->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'scheduled',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No active teams available for assignment');

        $this->service->autoAssignAppointments($date);
    }

    public function test_throws_exception_when_no_unassigned_appointments(): void
    {
        $team = Team::factory()->create(['is_active' => true]);

        $date = now()->addDay();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No unassigned appointments found for this date');

        $this->service->autoAssignAppointments($date);
    }

    public function test_groups_geocoded_appointments_by_proximity(): void
    {
        $team = Team::factory()->create(['is_active' => true, 'max_daily_appointments' => 20]);

        $date = now()->addDay();

        // Create cluster of nearby properties
        $property1 = Property::factory()->create([
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $property2 = Property::factory()->create([
            'latitude' => 40.7138,
            'longitude' => -74.0070,
        ]);

        $appointment1 = ServiceAppointment::factory()->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'scheduled',
            'property_id' => $property1->id,
        ]);

        $appointment2 = ServiceAppointment::factory()->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'scheduled',
            'property_id' => $property2->id,
        ]);

        $result = $this->service->autoAssignAppointments($date);

        // Both appointments should be assigned to the same team since they're close
        $this->assertEquals($appointment1->fresh()->team_id, $appointment2->fresh()->team_id);
    }

    public function test_calculates_team_capacities_correctly(): void
    {
        $team = Team::factory()->create(['is_active' => true, 'max_daily_appointments' => 10]);

        $date = now()->addDay();

        // Create 3 existing appointments for this team
        ServiceAppointment::factory()->count(3)->create([
            'scheduled_date' => $date,
            'team_id' => $team->id,
        ]);

        // Try to assign 8 new appointments
        ServiceAppointment::factory()->count(8)->create([
            'scheduled_date' => $date,
            'team_id' => null,
            'status' => 'scheduled',
        ]);

        $result = $this->service->autoAssignAppointments($date);

        // Should only assign 7 (team has capacity for 7 more: 10 max - 3 existing)
        $this->assertEquals(7, $result['total_assigned']);
        $this->assertEquals(1, $result['remaining_unassigned']);
    }
}
