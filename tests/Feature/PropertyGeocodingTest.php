<?php

namespace Tests\Feature;

use App\Events\PropertyCreated;
use App\Jobs\GeocodePropertyJob;
use App\Models\Customer;
use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PropertyGeocodingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_dispatches_property_created_event_when_property_is_created(): void
    {
        Event::fake([PropertyCreated::class]);

        $customer = Customer::factory()->create();

        $property = Property::create([
            'customer_id' => $customer->id,
            'address' => '123 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
            'service_status' => 'active',
        ]);

        Event::assertDispatchedTimes(PropertyCreated::class, 1);

        Event::assertDispatched(PropertyCreated::class, function ($event) use ($property) {
            return $event->property->id === $property->id;
        });
    }

    public function test_it_dispatches_geocode_job_when_geocoding_is_enabled(): void
    {
        Queue::fake();

        config(['services.google.geocoding_enabled' => true]);

        $customer = Customer::factory()->create();

        $property = Property::create([
            'customer_id' => $customer->id,
            'address' => '123 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
            'service_status' => 'active',
        ]);

        Queue::assertPushed(GeocodePropertyJob::class, function ($job) use ($property) {
            return $job->property->id === $property->id;
        });
    }

    public function test_it_does_not_dispatch_geocode_job_when_geocoding_is_disabled(): void
    {
        Queue::fake();

        config(['services.google.geocoding_enabled' => false]);

        $customer = Customer::factory()->create();

        $property = Property::create([
            'customer_id' => $customer->id,
            'address' => '456 Oak Ave',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62702',
            'service_status' => 'active',
        ]);

        Queue::assertNotPushed(GeocodePropertyJob::class);
    }

    public function test_it_geocodes_properties_created_during_customer_creation(): void
    {
        Queue::fake();

        config(['services.google.geocoding_enabled' => true]);

        $customer = Customer::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'status' => 'active',
        ]);

        Property::create([
            'customer_id' => $customer->id,
            'address' => '789 Elm St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62703',
            'service_status' => 'active',
        ]);

        Property::create([
            'customer_id' => $customer->id,
            'address' => '321 Pine Rd',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62704',
            'service_status' => 'active',
        ]);

        Queue::assertPushed(GeocodePropertyJob::class, 2);
    }

    public function test_it_geocodes_properties_created_via_observer(): void
    {
        Queue::fake();

        config(['services.google.geocoding_enabled' => true]);

        $customer = Customer::factory()->create();

        $property = Property::create([
            'customer_id' => $customer->id,
            'address' => '999 Maple Dr',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62705',
            'service_status' => 'active',
        ]);

        Queue::assertPushed(GeocodePropertyJob::class, 1);
    }
}
