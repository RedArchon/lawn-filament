<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Property;
use App\Models\ServiceAppointment;
use App\Models\ServiceType;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_invoice_from_appointments(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $serviceType = ServiceType::factory()->create(['company_id' => $company->id]);

        // Create completed service appointments
        $appointments = ServiceAppointment::factory()->count(3)->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'service_type_id' => $serviceType->id,
            'status' => 'completed',
        ]);

        $appointmentIds = $appointments->pluck('id')->toArray();

        $invoiceService = new InvoiceService;
        $invoice = $invoiceService->createFromAppointments($company, $customer, $appointmentIds);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($company->id, $invoice->company_id);
        $this->assertEquals($customer->id, $invoice->customer_id);
        $this->assertEquals('draft', $invoice->status);

        // Check that line items were created
        $this->assertCount(3, $invoice->items);

        // Check that appointments were marked as invoiced
        foreach ($appointments as $appointment) {
            $appointment->refresh();
            $this->assertNotNull($appointment->invoiced_at);
        }
    }

    public function test_line_items_created_correctly(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $serviceType = ServiceType::create([
            'company_id' => $company->id,
            'name' => 'Mowing',
            'default_price' => 50.00,
            'default_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $appointment = ServiceAppointment::create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'service_type_id' => $serviceType->id,
            'status' => 'completed',
            'scheduled_date' => now()->toDateString(),
        ]);

        $invoiceService = new InvoiceService;
        $invoice = $invoiceService->createFromAppointments($company, $customer, [$appointment->id]);

        $lineItem = $invoice->items->first();
        $this->assertEquals($appointment->id, $lineItem->service_appointment_id);
        $this->assertStringContainsString('Mowing', $lineItem->description);
        $this->assertEquals(1, $lineItem->quantity);
        $this->assertEquals(50.00, $lineItem->unit_price);
        $this->assertEquals(50.00, $lineItem->line_total);
    }

    public function test_appointments_marked_with_invoiced_at(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $serviceType = ServiceType::factory()->create(['company_id' => $company->id]);

        $appointment = ServiceAppointment::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'service_type_id' => $serviceType->id,
            'status' => 'completed',
        ]);

        $this->assertNull($appointment->invoiced_at);

        $invoiceService = new InvoiceService;
        $invoiceService->createFromAppointments($company, $customer, [$appointment->id]);

        $appointment->refresh();
        $this->assertNotNull($appointment->invoiced_at);
    }

    public function test_totals_calculate_correctly(): void
    {
        $company = Company::factory()->create(['default_tax_rate' => 8.5]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $property = Property::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'address' => '456 Test Avenue',
            'city' => 'Test City',
            'state' => 'FL',
            'zip' => '12345',
        ]);
        $serviceType = ServiceType::create([
            'company_id' => $company->id,
            'name' => 'Test Service',
            'default_price' => 100.00,
            'default_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $appointment = ServiceAppointment::create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'service_type_id' => $serviceType->id,
            'status' => 'completed',
            'scheduled_date' => now()->toDateString(),
        ]);

        $invoiceService = new InvoiceService;
        $invoice = $invoiceService->createFromAppointments($company, $customer, [$appointment->id]);

        $this->assertEquals(100.00, $invoice->subtotal);
        $this->assertEquals(8.50, $invoice->tax_amount);
        $this->assertEquals(108.50, $invoice->total);
    }

    public function test_tenant_isolation_auto_assigns_company_id(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $property = Property::factory()->create(['company_id' => $company->id, 'customer_id' => $customer->id]);
        $serviceType = ServiceType::factory()->create(['company_id' => $company->id]);

        $appointment = ServiceAppointment::factory()->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'service_type_id' => $serviceType->id,
            'status' => 'completed',
        ]);

        $invoiceService = new InvoiceService;
        $invoice = $invoiceService->createFromAppointments($company, $customer, [$appointment->id]);

        $this->assertEquals($company->id, $invoice->company_id);
    }

    public function test_invoice_requires_customer_id(): void
    {
        $company = Company::factory()->create();

        $this->expectException(\Illuminate\Database\QueryException::class);
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => null, // Missing customer
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 100,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 100,
        ]);
    }
}
