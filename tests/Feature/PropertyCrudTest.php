<?php

namespace Tests\Feature;

use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Jobs\GeocodePropertyJob;
use App\Models\Customer;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class PropertyCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        Queue::fake();
    }

    public function test_can_render_property_list_page(): void
    {
        Livewire::test(ListProperties::class)
            ->assertOk();
    }

    public function test_can_list_properties(): void
    {
        $properties = Property::factory()->count(10)->create();

        Livewire::test(ListProperties::class)
            ->assertCanSeeTableRecords($properties);
    }

    public function test_can_render_property_create_page(): void
    {
        Livewire::test(CreateProperty::class)
            ->assertOk();
    }

    public function test_can_create_a_property(): void
    {
        $customer = Customer::factory()->create();
        $newPropertyData = Property::factory()->make();

        Livewire::test(CreateProperty::class)
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

        $this->assertDatabaseHas(Property::class, [
            'customer_id' => $customer->id,
            'address' => $newPropertyData->address,
            'city' => $newPropertyData->city,
        ]);
    }

    public function test_validates_required_fields_when_creating_a_property(): void
    {
        Livewire::test(CreateProperty::class)
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
    }

    public function test_can_render_property_edit_page(): void
    {
        $property = Property::factory()->create();

        Livewire::test(EditProperty::class, [
            'record' => $property->id,
        ])
            ->assertOk()
            ->assertSchemaStateSet([
                'address' => $property->address,
                'city' => $property->city,
            ]);
    }

    public function test_can_update_a_property(): void
    {
        $property = Property::factory()->create();
        $newPropertyData = Property::factory()->make();

        Livewire::test(EditProperty::class, [
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

        $this->assertDatabaseHas(Property::class, [
            'id' => $property->id,
            'address' => $newPropertyData->address,
            'city' => $newPropertyData->city,
        ]);
    }

    public function test_can_delete_a_property(): void
    {
        $property = Property::factory()->create();

        Livewire::test(EditProperty::class, [
            'record' => $property->id,
        ])
            ->callAction('delete')
            ->assertNotified();

        $this->assertDatabaseMissing(Property::class, [
            'id' => $property->id,
            'deleted_at' => null,
        ]);
    }

    public function test_can_filter_properties_by_service_status(): void
    {
        Property::factory()->count(5)->create(['service_status' => 'active']);
        $inactiveProperties = Property::factory()->count(3)->create(['service_status' => 'inactive']);

        Livewire::test(ListProperties::class)
            ->filterTable('service_status', 'inactive')
            ->assertCanSeeTableRecords($inactiveProperties)
            ->assertCountTableRecords(3);
    }

    public function test_can_filter_properties_by_customer(): void
    {
        $customer = Customer::factory()->create();
        $customerProperties = Property::factory()->count(3)->create(['customer_id' => $customer->id]);
        Property::factory()->count(5)->create();

        Livewire::test(ListProperties::class)
            ->filterTable('customer_id', $customer->id)
            ->assertCanSeeTableRecords($customerProperties)
            ->assertCountTableRecords(3);
    }

    public function test_can_search_properties_by_address(): void
    {
        $properties = Property::factory()->count(5)->create();
        $targetProperty = $properties->first();

        Livewire::test(ListProperties::class)
            ->searchTable($targetProperty->address)
            ->assertCanSeeTableRecords([$targetProperty]);
    }

    public function test_dispatches_geocoding_job_when_address_changes(): void
    {
        $property = Property::factory()->create();

        Livewire::test(EditProperty::class, [
            'record' => $property->id,
        ])
            ->fillForm([
                'address' => '456 New Street',
            ])
            ->call('save');

        Queue::assertPushed(GeocodePropertyJob::class);
    }
}
