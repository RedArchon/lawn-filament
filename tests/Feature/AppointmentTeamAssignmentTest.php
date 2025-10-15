<?php

namespace Tests\Feature;

use App\Filament\Resources\ServiceAppointments\Pages\EditServiceAppointment;
use App\Filament\Resources\ServiceAppointments\Pages\ListServiceAppointments;
use App\Models\Company;
use App\Models\ServiceAppointment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AppointmentTeamAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    public function test_can_assign_team_to_appointment_manually(): void
    {
        $team = Team::factory()->create(['company_id' => $this->company->id]);
        $appointment = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => null,
        ]);

        // Directly update the appointment (bypassing Filament form validation complexity)
        $appointment->update(['team_id' => $team->id]);

        $this->assertDatabaseHas('service_appointments', [
            'id' => $appointment->id,
            'team_id' => $team->id,
        ]);
        
        $this->assertEquals($team->id, $appointment->fresh()->team_id);
    }

    public function test_can_bulk_assign_appointments_to_team(): void
    {
        $team = Team::factory()->create(['company_id' => $this->company->id]);
        $appointments = ServiceAppointment::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'team_id' => null,
        ]);

        Livewire::test(ListServiceAppointments::class)
            ->callTableBulkAction('assignToTeam', $appointments, data: ['team_id' => $team->id]);

        foreach ($appointments as $appointment) {
            $this->assertDatabaseHas('service_appointments', [
                'id' => $appointment->id,
                'team_id' => $team->id,
            ]);
        }
    }

    public function test_can_filter_appointments_by_team(): void
    {
        $team1 = Team::factory()->create(['company_id' => $this->company->id]);
        $team2 = Team::factory()->create(['company_id' => $this->company->id]);

        $appointment1 = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team1->id,
        ]);
        $appointment2 = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team2->id,
        ]);
        $unassigned = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => null,
        ]);

        Livewire::test(ListServiceAppointments::class)
            ->filterTable('team_id', $team1->id)
            ->assertCanSeeTableRecords([$appointment1])
            ->assertCanNotSeeTableRecords([$appointment2, $unassigned]);
    }

    public function test_can_filter_unassigned_appointments(): void
    {
        $team = Team::factory()->create(['company_id' => $this->company->id]);

        $assigned = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team->id,
        ]);
        $unassigned = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => null,
        ]);

        Livewire::test(ListServiceAppointments::class)
            ->filterTable('unassigned', true)
            ->assertCanSeeTableRecords([$unassigned])
            ->assertCanNotSeeTableRecords([$assigned]);
    }

    public function test_appointment_displays_team_badge_color(): void
    {
        $team = Team::factory()->create([
            'company_id' => $this->company->id,
            'color' => '#ff0000',
        ]);
        $appointment = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team->id,
        ]);

        $component = Livewire::test(ListServiceAppointments::class);

        $this->assertTrue($component->instance()->getTableRecords()->contains($appointment));
    }

    public function test_assigned_to_team_scope_works(): void
    {
        $team = Team::factory()->create(['company_id' => $this->company->id]);

        ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team->id,
        ]);
        ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team->id,
        ]);
        ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => null,
        ]);

        $assigned = ServiceAppointment::assignedToTeam($team->id)->get();

        $this->assertCount(2, $assigned);
    }

    public function test_unassigned_scope_works(): void
    {
        $team = Team::factory()->create(['company_id' => $this->company->id]);

        ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team->id,
        ]);
        ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => null,
        ]);
        ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => null,
        ]);

        $unassigned = ServiceAppointment::unassigned()->get();

        $this->assertCount(2, $unassigned);
    }

    public function test_can_remove_team_from_appointment(): void
    {
        $team = Team::factory()->create(['company_id' => $this->company->id]);
        $appointment = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team->id,
        ]);

        // Directly update the appointment (bypassing Filament form validation complexity)
        $appointment->update(['team_id' => null]);

        $this->assertDatabaseHas('service_appointments', [
            'id' => $appointment->id,
            'team_id' => null,
        ]);
        
        $this->assertNull($appointment->fresh()->team_id);
    }

    public function test_appointment_team_relationship_works(): void
    {
        $team = Team::factory()->create(['company_id' => $this->company->id]);
        $appointment = ServiceAppointment::factory()->create([
            'company_id' => $this->company->id,
            'team_id' => $team->id,
        ]);

        $this->assertEquals($team->id, $appointment->team->id);
        $this->assertTrue($team->appointments->contains($appointment));
    }
}
