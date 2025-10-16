<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Property;
use App\Models\ServiceAppointment;
use App\Models\ServiceSchedule;
use App\Models\ServiceType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_only_see_their_companys_customers(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);

        $customer1 = Customer::factory()->create(['company_id' => $company1->id]);
        $customer2 = Customer::factory()->create(['company_id' => $company2->id]);

        // User 1 should only see company 1's customer
        $this->actingAs($user1);
        $visibleCustomers = Customer::all();
        $this->assertCount(1, $visibleCustomers);
        $this->assertTrue($visibleCustomers->contains($customer1));
        $this->assertFalse($visibleCustomers->contains($customer2));

        // User 2 should only see company 2's customer
        $this->actingAs($user2);
        $visibleCustomers = Customer::all();
        $this->assertCount(1, $visibleCustomers);
        $this->assertTrue($visibleCustomers->contains($customer2));
        $this->assertFalse($visibleCustomers->contains($customer1));
    }

    public function test_creating_records_auto_assigns_company_id(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $this->assertEquals($company->id, $customer->company_id);
    }

    public function test_properties_are_tenant_scoped(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);

        $property1 = Property::factory()->create(['company_id' => $company1->id]);
        $property2 = Property::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user1);
        $visibleProperties = Property::all();
        $this->assertCount(1, $visibleProperties);
        $this->assertEquals($property1->id, $visibleProperties->first()->id);

        $this->actingAs($user2);
        $visibleProperties = Property::all();
        $this->assertCount(1, $visibleProperties);
        $this->assertEquals($property2->id, $visibleProperties->first()->id);
    }

    public function test_service_types_are_tenant_scoped(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);

        $serviceType1 = ServiceType::factory()->create(['company_id' => $company1->id]);
        $serviceType2 = ServiceType::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user1);
        $visibleServiceTypes = ServiceType::all();
        $this->assertCount(1, $visibleServiceTypes);
        $this->assertTrue($visibleServiceTypes->contains($serviceType1));
        $this->assertFalse($visibleServiceTypes->contains($serviceType2));
    }

    public function test_teams_are_tenant_scoped(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);

        $team1 = Team::factory()->create(['company_id' => $company1->id]);
        $team2 = Team::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user1);
        $visibleTeams = Team::all();
        $this->assertCount(1, $visibleTeams);
        $this->assertEquals($team1->id, $visibleTeams->first()->id);
    }

    public function test_service_schedules_are_tenant_scoped(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);

        $schedule1 = ServiceSchedule::factory()->create(['company_id' => $company1->id]);
        $schedule2 = ServiceSchedule::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user1);
        $visibleSchedules = ServiceSchedule::all();
        $this->assertCount(1, $visibleSchedules);
        $this->assertEquals($schedule1->id, $visibleSchedules->first()->id);
    }

    public function test_service_appointments_are_tenant_scoped(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);

        $appointment1 = ServiceAppointment::factory()->create(['company_id' => $company1->id]);
        $appointment2 = ServiceAppointment::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user1);
        $visibleAppointments = ServiceAppointment::all();
        $this->assertCount(1, $visibleAppointments);
        $this->assertEquals($appointment1->id, $visibleAppointments->first()->id);
    }

    public function test_seeder_creates_multiple_companies_with_isolated_data(): void
    {
        $this->artisan('db:seed');

        $companies = Company::all();
        $this->assertGreaterThan(1, $companies->count());

        foreach ($companies as $company) {
            $user = User::factory()->create(['company_id' => $company->id]);
            $this->actingAs($user);

            // Each company should have its own customers
            $customers = Customer::all();
            foreach ($customers as $customer) {
                $this->assertEquals($company->id, $customer->company_id);
            }
        }
    }

    public function test_cannot_access_other_company_record_by_id(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company1->id]);

        $customer2 = Customer::factory()->create(['company_id' => $company2->id]);

        $this->actingAs($user1);

        // Try to find the other company's customer
        $foundCustomer = Customer::find($customer2->id);
        $this->assertNull($foundCustomer);
    }

    public function test_user_company_relationship_works(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertEquals($company->id, $user->company->id);
        $this->assertTrue($company->users->contains($user));
    }
}
