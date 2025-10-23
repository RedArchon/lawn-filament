<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_total_equals_quantity_times_unit_price(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Test Service',
            'quantity' => 2.5,
            'unit_price' => 40.00,
        ]);

        $this->assertEquals(100.00, $item->line_total);
    }

    public function test_subtotal_equals_sum_of_line_totals(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service 1',
            'quantity' => 1,
            'unit_price' => 50.00,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service 2',
            'quantity' => 2,
            'unit_price' => 25.00,
        ]);

        $invoiceService = new InvoiceService;
        $invoiceService->calculateTotals($invoice);

        $this->assertEquals(100.00, $invoice->subtotal);
    }

    public function test_tax_amount_equals_subtotal_times_tax_rate(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'tax_rate' => 8.5,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $invoiceService = new InvoiceService;
        $invoiceService->calculateTotals($invoice);

        $this->assertEquals(100.00, $invoice->subtotal);
        $this->assertEquals(8.50, $invoice->tax_amount);
    }

    public function test_total_equals_subtotal_plus_tax_amount(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'tax_rate' => 10.0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $invoiceService = new InvoiceService;
        $invoiceService->calculateTotals($invoice);

        $this->assertEquals(100.00, $invoice->subtotal);
        $this->assertEquals(10.00, $invoice->tax_amount);
        $this->assertEquals(110.00, $invoice->total);
    }

    public function test_recalculation_when_items_change(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'tax_rate' => 5.0,
        ]);

        // Create initial item
        $item = InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $this->assertEquals(100.00, $invoice->fresh()->subtotal);
        $this->assertEquals(5.00, $invoice->fresh()->tax_amount);
        $this->assertEquals(105.00, $invoice->fresh()->total);

        // Update item
        $item->update([
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        $this->assertEquals(100.00, $invoice->fresh()->subtotal);
        $this->assertEquals(5.00, $invoice->fresh()->tax_amount);
        $this->assertEquals(105.00, $invoice->fresh()->total);

        // Delete item
        $item->delete();

        $this->assertEquals(0.00, $invoice->fresh()->subtotal);
        $this->assertEquals(0.00, $invoice->fresh()->tax_amount);
        $this->assertEquals(0.00, $invoice->fresh()->total);
    }

    public function test_zero_tax_rate_calculation(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'tax_rate' => 0.0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $invoiceService = new InvoiceService;
        $invoiceService->calculateTotals($invoice);

        $this->assertEquals(100.00, $invoice->subtotal);
        $this->assertEquals(0.00, $invoice->tax_amount);
        $this->assertEquals(100.00, $invoice->total);
    }
}
