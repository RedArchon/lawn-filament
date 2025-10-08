<?php

namespace Tests\Feature;

use App\Filament\Resources\Teams\Pages\CreateTeam;
use App\Filament\Resources\Teams\Pages\EditTeam;
use App\Filament\Resources\Teams\Pages\ListTeams;
use App\Models\ServiceAppointment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_teams(): void
    {
        $teams = Team::factory()->count(3)->create(['is_active' => true]);

        Livewire::test(ListTeams::class)
            ->filterTable('active', true)
            ->assertCanSeeTableRecords($teams);
    }

    public function test_can_create_team(): void
    {
        $teamData = [
            'name' => 'Alpha Crew',
            'color' => '#3b82f6',
            'is_active' => true,
            'max_daily_appointments' => 15,
            'start_time' => '08:00:00',
            'notes' => 'Morning crew',
        ];

        Livewire::test(CreateTeam::class)
            ->fillForm($teamData)
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('teams', [
            'name' => 'Alpha Crew',
            'color' => '#3b82f6',
            'is_active' => true,
            'max_daily_appointments' => 15,
        ]);
    }

    public function test_can_edit_team(): void
    {
        $team = Team::factory()->create([
            'name' => 'Original Name',
        ]);

        Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
            ->assertFormSet([
                'name' => 'Original Name',
            ])
            ->fillForm([
                'name' => 'Updated Name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_assign_users_to_team(): void
    {
        $team = Team::factory()->create();
        $users = User::factory()->count(3)->create();

        Livewire::test(EditTeam::class, ['record' => $team->getRouteKey()])
            ->fillForm([
                'users' => $users->pluck('id')->toArray(),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertEquals(3, $team->fresh()->users()->count());
    }

    public function test_can_delete_team(): void
    {
        $team = Team::factory()->create();

        $team->delete();

        $this->assertSoftDeleted('teams', [
            'id' => $team->id,
        ]);
    }

    public function test_active_scope_filters_active_teams(): void
    {
        Team::factory()->create(['is_active' => true]);
        Team::factory()->create(['is_active' => false]);

        $activeTeams = Team::active()->get();

        $this->assertCount(1, $activeTeams);
    }

    public function test_team_has_appointments_relationship(): void
    {
        $team = Team::factory()->create();
        $appointment = ServiceAppointment::factory()->create(['team_id' => $team->id]);

        $this->assertTrue($team->appointments->contains($appointment));
    }

    public function test_team_has_users_relationship(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create();

        $team->users()->attach($user->id);

        $this->assertTrue($team->users->contains($user));
        $this->assertTrue($user->teams->contains($team));
    }

    public function test_team_name_is_required(): void
    {
        Livewire::test(CreateTeam::class)
            ->fillForm([
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_can_filter_teams_by_active_status(): void
    {
        $activeTeam = Team::factory()->create(['is_active' => true]);
        $inactiveTeam = Team::factory()->create(['is_active' => false]);

        Livewire::test(ListTeams::class)
            ->filterTable('active', true)
            ->assertCanSeeTableRecords([$activeTeam])
            ->assertCanNotSeeTableRecords([$inactiveTeam]);
    }
}
