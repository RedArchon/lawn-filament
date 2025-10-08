<?php

namespace Tests\Feature;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_can_render_customer_list_page(): void
    {
        Livewire::test(ListCustomers::class)
            ->assertOk();
    }

    public function test_can_list_customers(): void
    {
        $customers = Customer::factory()->count(10)->create();

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
        $customer = Customer::factory()->create();

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
        $customer = Customer::factory()->create();
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
        $customer = Customer::factory()->create();

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
        $customers = Customer::factory()->count(5)->create();
        $targetCustomer = $customers->first();

        Livewire::test(ListCustomers::class)
            ->searchTable($targetCustomer->name)
            ->assertCanSeeTableRecords([$targetCustomer])
            ->assertCanNotSeeTableRecords($customers->skip(1));
    }

    public function test_can_search_customers_by_email(): void
    {
        $customers = Customer::factory()->count(5)->create();
        $targetCustomer = $customers->last();

        Livewire::test(ListCustomers::class)
            ->searchTable($targetCustomer->email)
            ->assertCanSeeTableRecords([$targetCustomer])
            ->assertCanNotSeeTableRecords($customers->take($customers->count() - 1));
    }

    public function test_can_filter_customers_by_status(): void
    {
        Customer::factory()->count(5)->create(['status' => 'active']);
        $inactiveCustomers = Customer::factory()->count(3)->create(['status' => 'inactive']);

        Livewire::test(ListCustomers::class)
            ->filterTable('status', 'inactive')
            ->assertCanSeeTableRecords($inactiveCustomers)
            ->assertCountTableRecords(3);
    }
}
