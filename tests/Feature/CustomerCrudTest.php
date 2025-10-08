<?php

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('can render customer list page', function () {
    livewire(ListCustomers::class)
        ->assertOk();
});

it('can list customers', function () {
    $customers = Customer::factory()->count(10)->create();

    livewire(ListCustomers::class)
        ->assertCanSeeTableRecords($customers);
});

it('can render customer create page', function () {
    livewire(CreateCustomer::class)
        ->assertOk();
});

it('can create a customer', function () {
    $newCustomerData = Customer::factory()->make();

    livewire(CreateCustomer::class)
        ->fillForm([
            'name' => $newCustomerData->name,
            'email' => $newCustomerData->email,
            'phone' => $newCustomerData->phone,
            'status' => 'active',
        ])
        ->call('create')
        ->assertNotified();

    assertDatabaseHas(Customer::class, [
        'name' => $newCustomerData->name,
        'email' => $newCustomerData->email,
        'phone' => $newCustomerData->phone,
        'status' => 'active',
    ]);
});

it('validates required fields when creating a customer', function () {
    livewire(CreateCustomer::class)
        ->fillForm([
            'name' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required'])
        ->assertNotNotified();
});

it('validates email format when creating a customer', function () {
    livewire(CreateCustomer::class)
        ->fillForm([
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ])
        ->call('create')
        ->assertHasFormErrors(['email' => 'email'])
        ->assertNotNotified();
});

it('can render customer edit page', function () {
    $customer = Customer::factory()->create();

    livewire(EditCustomer::class, [
        'record' => $customer->id,
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'name' => $customer->name,
            'email' => $customer->email,
        ]);
});

it('can update a customer', function () {
    $customer = Customer::factory()->create();
    $newCustomerData = Customer::factory()->make();

    livewire(EditCustomer::class, [
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

    assertDatabaseHas(Customer::class, [
        'id' => $customer->id,
        'name' => $newCustomerData->name,
        'email' => $newCustomerData->email,
    ]);
});

it('can delete a customer', function () {
    $customer = Customer::factory()->create();

    livewire(EditCustomer::class, [
        'record' => $customer->id,
    ])
        ->callAction('delete')
        ->assertNotified();

    assertDatabaseMissing(Customer::class, [
        'id' => $customer->id,
        'deleted_at' => null,
    ]);
});

it('can search customers by name', function () {
    $customers = Customer::factory()->count(5)->create();
    $targetCustomer = $customers->first();

    livewire(ListCustomers::class)
        ->searchTable($targetCustomer->name)
        ->assertCanSeeTableRecords([$targetCustomer])
        ->assertCanNotSeeTableRecords($customers->skip(1));
});

it('can search customers by email', function () {
    $customers = Customer::factory()->count(5)->create();
    $targetCustomer = $customers->last();

    livewire(ListCustomers::class)
        ->searchTable($targetCustomer->email)
        ->assertCanSeeTableRecords([$targetCustomer])
        ->assertCanNotSeeTableRecords($customers->take($customers->count() - 1));
});

it('can filter customers by status', function () {
    Customer::factory()->count(5)->create(['status' => 'active']);
    $inactiveCustomers = Customer::factory()->count(3)->create(['status' => 'inactive']);

    livewire(ListCustomers::class)
        ->filterTable('status', 'inactive')
        ->assertCanSeeTableRecords($inactiveCustomers)
        ->assertCountTableRecords(3);
});
