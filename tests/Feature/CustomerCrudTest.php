<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    public function test_can_render_customer_list_page(): void
    {
        Livewire::test(ListCustomers::class)
            ->assertOk();
    }

    public function test_can_list_customers(): void
    {
        $customers = Customer::factory()->count(10)->create(['company_id' => $this->company->id]);

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords($customers);
    }

    public function test_can_render_customer_create_page(): void
    {
        Livewire::test(CreateCustomer::class)
            ->assertOk();
    }

    public function test_can_create_a_customer(): void
    {
        $newCustomerData = Customer::factory()->make();

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => $newCustomerData->name,
                'email' => $newCustomerData->email,
                'phone' => $newCustomerData->phone,
                'status' => 'active',
            ])
            ->call('create')
            ->assertNotified();

        $this->assertDatabaseHas(Customer::class, [
            'name' => $newCustomerData->name,
            'email' => $newCustomerData->email,
            'phone' => $newCustomerData->phone,
            'status' => 'active',
        ]);
    }

    public function test_validates_required_fields_when_creating_a_customer(): void
    {
        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required'])
            ->assertNotNotified();
    }

    public function test_validates_email_format_when_creating_a_customer(): void
    {
        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'John Doe',
                'email' => 'invalid-email',
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'email'])
            ->assertNotNotified();
    }

    public function test_can_render_customer_edit_page(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        Livewire::test(EditCustomer::class, [
            'record' => $customer->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'name' => $customer->name,
                'email' => $customer->email,
            ]);
    }

    public function test_can_update_a_customer(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $newCustomerData = Customer::factory()->make();

        Livewire::test(EditCustomer::class, [
            'record' => $customer->id,
        ])
            ->fillForm([
                'name' => $newCustomerData->name,
                'email' => $newCustomerData->email,
                'phone' => $newCustomerData->phone,
                'company_name' => $newCustomerData->company_name,
            ])
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseHas(Customer::class, [
            'id' => $customer->id,
            'name' => $newCustomerData->name,
            'email' => $newCustomerData->email,
        ]);
    }

    public function test_can_delete_a_customer(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        Livewire::test(EditCustomer::class, [
            'record' => $customer->id,
        ])
            ->callAction('delete')
            ->assertNotified();

        $this->assertDatabaseMissing(Customer::class, [
            'id' => $customer->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_search_customers_by_name(): void
    {
        $customers = Customer::factory()->count(5)->create(['company_id' => $this->company->id]);
        $targetCustomer = $customers->first();

        Livewire::test(ListCustomers::class)
            ->searchTable($targetCustomer->name)
            ->assertCanSeeTableRecords([$targetCustomer])
            ->assertCanNotSeeTableRecords($customers->skip(1));
    }

    public function test_can_search_customers_by_email(): void
    {
        $customers = Customer::factory()->count(5)->create(['company_id' => $this->company->id]);
        $targetCustomer = $customers->last();

        Livewire::test(ListCustomers::class)
            ->searchTable($targetCustomer->email)
            ->assertCanSeeTableRecords([$targetCustomer])
            ->assertCanNotSeeTableRecords($customers->take($customers->count() - 1));
    }

    public function test_can_filter_customers_by_status(): void
    {
        Customer::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        $inactiveCustomers = Customer::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'inactive',
        ]);

        Livewire::test(ListCustomers::class)
            ->filterTable('status', 'inactive')
            ->assertCanSeeTableRecords($inactiveCustomers)
            ->assertCountTableRecords(3);
    }

    public function test_can_create_customer_with_service_billing_address_checkbox(): void
    {
        $newCustomerData = Customer::factory()->make([
            'billing_address' => '123 Test Street',
            'billing_city' => 'TestCity',
            'billing_state' => 'CA',
            'billing_zip' => '12345',
        ]);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => $newCustomerData->name,
                'email' => $newCustomerData->email,
                'phone' => $newCustomerData->phone,
                'status' => 'active',
                'billing_address' => $newCustomerData->billing_address,
                'billing_city' => $newCustomerData->billing_city,
                'billing_state' => $newCustomerData->billing_state,
                'billing_zip' => $newCustomerData->billing_zip,
                'service_billing_address' => true,
            ])
            ->call('create')
            ->assertNotified();

        $customer = Customer::where('email', $newCustomerData->email)->first();
        $this->assertNotNull($customer);

        // Verify property was created
        $this->assertCount(1, $customer->properties);
        $property = $customer->properties->first();
        $this->assertEquals($newCustomerData->billing_address, $property->address);
        $this->assertEquals($newCustomerData->billing_city, $property->city);
        $this->assertEquals($newCustomerData->billing_state, $property->state);
        $this->assertEquals($newCustomerData->billing_zip, $property->zip);
        $this->assertEquals('active', $property->service_status);
    }

    public function test_can_edit_customer_and_add_property_from_billing_address(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'billing_address' => '456 Edit Street',
            'billing_city' => 'EditCity',
            'billing_state' => 'NY',
            'billing_zip' => '54321',
        ]);

        Livewire::test(EditCustomer::class, [
            'record' => $customer->id,
        ])
            ->fillForm([
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'billing_address' => $customer->billing_address,
                'billing_city' => $customer->billing_city,
                'billing_state' => $customer->billing_state,
                'billing_zip' => $customer->billing_zip,
                'service_billing_address' => true,
            ])
            ->call('save')
            ->assertNotified();

        $customer->refresh();
        $this->assertCount(1, $customer->properties);
        $property = $customer->properties->first();
        $this->assertEquals('456 Edit Street', $property->address);
    }

    public function test_does_not_create_duplicate_property_from_billing_address(): void
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'billing_address' => '789 Duplicate St',
            'billing_city' => 'DupeCity',
            'billing_state' => 'TX',
            'billing_zip' => '67890',
        ]);

        // Create initial property with same address
        Property::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'address' => '789 Duplicate St',
            'city' => 'DupeCity',
            'state' => 'TX',
            'zip' => '67890',
        ]);

        Livewire::test(EditCustomer::class, [
            'record' => $customer->id,
        ])
            ->fillForm([
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'billing_address' => $customer->billing_address,
                'billing_city' => $customer->billing_city,
                'billing_state' => $customer->billing_state,
                'billing_zip' => $customer->billing_zip,
                'service_billing_address' => true,
            ])
            ->call('save')
            ->assertNotified();

        $customer->refresh();
        // Should still only have 1 property, not create duplicate
        $this->assertCount(1, $customer->properties);
    }
}
