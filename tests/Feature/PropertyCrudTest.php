<?php

use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Jobs\GeocodePropertyJob;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
    Queue::fake();
});

it('can render property list page', function () {
    livewire(ListProperties::class)
        ->assertOk();
});

it('can list properties', function () {
    $properties = Property::factory()->count(10)->create();

    livewire(ListProperties::class)
        ->assertCanSeeTableRecords($properties);
});

it('can render property create page', function () {
    livewire(CreateProperty::class)
        ->assertOk();
});

it('can create a property', function () {
    $customer = Customer::factory()->create();
    $newPropertyData = Property::factory()->make();

    livewire(CreateProperty::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'address' => $newPropertyData->address,
            'city' => $newPropertyData->city,
            'state' => $newPropertyData->state,
            'zip' => $newPropertyData->zip,
            'service_status' => 'active',
        ])
        ->call('create')
        ->assertNotified();

    assertDatabaseHas(Property::class, [
        'customer_id' => $customer->id,
        'address' => $newPropertyData->address,
        'city' => $newPropertyData->city,
    ]);

    Queue::assertPushed(GeocodePropertyJob::class);
});

it('validates required fields when creating a property', function () {
    livewire(CreateProperty::class)
        ->fillForm([
            'address' => null,
            'city' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'customer_id' => 'required',
            'address' => 'required',
            'city' => 'required',
        ])
        ->assertNotNotified();
});

it('can render property edit page', function () {
    $property = Property::factory()->create();

    livewire(EditProperty::class, [
        'record' => $property->id,
    ])
        ->assertOk()
        ->assertSchemaStateSet([
            'address' => $property->address,
            'city' => $property->city,
        ]);
});

it('can update a property', function () {
    $property = Property::factory()->create();
    $newPropertyData = Property::factory()->make();

    livewire(EditProperty::class, [
        'record' => $property->id,
    ])
        ->fillForm([
            'address' => $newPropertyData->address,
            'city' => $newPropertyData->city,
            'state' => $newPropertyData->state,
            'zip' => $newPropertyData->zip,
        ])
        ->call('save')
        ->assertNotified();

    assertDatabaseHas(Property::class, [
        'id' => $property->id,
        'address' => $newPropertyData->address,
        'city' => $newPropertyData->city,
    ]);
});

it('can delete a property', function () {
    $property = Property::factory()->create();

    livewire(EditProperty::class, [
        'record' => $property->id,
    ])
        ->callAction('delete')
        ->assertNotified();

    assertDatabaseMissing(Property::class, [
        'id' => $property->id,
        'deleted_at' => null,
    ]);
});

it('can filter properties by service status', function () {
    Property::factory()->count(5)->create(['service_status' => 'active']);
    $inactiveProperties = Property::factory()->count(3)->create(['service_status' => 'inactive']);

    livewire(ListProperties::class)
        ->filterTable('service_status', 'inactive')
        ->assertCanSeeTableRecords($inactiveProperties)
        ->assertCountTableRecords(3);
});

it('can filter properties by customer', function () {
    $customer = Customer::factory()->create();
    $customerProperties = Property::factory()->count(3)->create(['customer_id' => $customer->id]);
    Property::factory()->count(5)->create();

    livewire(ListProperties::class)
        ->filterTable('customer_id', $customer->id)
        ->assertCanSeeTableRecords($customerProperties)
        ->assertCountTableRecords(3);
});

it('can search properties by address', function () {
    $properties = Property::factory()->count(5)->create();
    $targetProperty = $properties->first();

    livewire(ListProperties::class)
        ->searchTable($targetProperty->address)
        ->assertCanSeeTableRecords([$targetProperty]);
});

it('dispatches geocoding job when address changes', function () {
    $property = Property::factory()->create();

    livewire(EditProperty::class, [
        'record' => $property->id,
    ])
        ->fillForm([
            'address' => '456 New Street',
        ])
        ->call('save');

    Queue::assertPushed(GeocodePropertyJob::class);
});
