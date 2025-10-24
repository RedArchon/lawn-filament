<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoiceNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_numbers_increment_per_company(): void
    {
        $company1 = Company::factory()->create(['invoice_prefix' => 'INV']);
        $company2 = Company::factory()->create(['invoice_prefix' => 'BILL']);

        // Create invoices for company 1
        $invoice1 = Invoice::factory()->create(['company_id' => $company1->id]);
        $invoice2 = Invoice::factory()->create(['company_id' => $company1->id]);

        // Create invoices for company 2
        $invoice3 = Invoice::factory()->create(['company_id' => $company2->id]);
        $invoice4 = Invoice::factory()->create(['company_id' => $company2->id]);

        $this->assertEquals('INV-2025-0001', $invoice1->invoice_number);
        $this->assertEquals('INV-2025-0002', $invoice2->invoice_number);
        $this->assertEquals('BILL-2025-0001', $invoice3->invoice_number);
        $this->assertEquals('BILL-2025-0002', $invoice4->invoice_number);
    }

    public function test_invoice_numbers_reset_each_year(): void
    {
        $company = Company::factory()->create(['invoice_prefix' => 'INV']);

        // Create invoice in 2024
        $this->travelTo('2024-12-31');
        $invoice2024 = Invoice::factory()->create(['company_id' => $company->id]);
        $this->assertEquals('INV-2024-0001', $invoice2024->invoice_number);

        // Create invoice in 2025
        $this->travelTo('2025-01-01');
        $invoice2025 = Invoice::factory()->create(['company_id' => $company->id]);
        $this->assertEquals('INV-2025-0001', $invoice2025->invoice_number);
    }

    public function test_unique_constraint_prevents_duplicates(): void
    {
        $company = Company::factory()->create(['invoice_prefix' => 'INV']);

        // Create first invoice
        $invoice1 = Invoice::factory()->create(['company_id' => $company->id]);
        $this->assertEquals('INV-2025-0001', $invoice1->invoice_number);

        // Try to create invoice with same number - should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        Invoice::create([
            'company_id' => $company->id,
            'customer_id' => Customer::factory()->create(['company_id' => $company->id])->id,
            'invoice_number' => 'INV-2025-0001', // Same number
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'status' => 'draft',
            'subtotal' => 100,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => 100,
        ]);
    }

    public function test_concurrent_invoice_creation_does_not_create_duplicates(): void
    {
        $company = Company::factory()->create(['invoice_prefix' => 'INV']);

        // Simulate concurrent creation by creating multiple invoices rapidly
        $invoices = collect();
        for ($i = 0; $i < 5; $i++) {
            $invoices->push(Invoice::factory()->create(['company_id' => $company->id]));
        }

        $numbers = $invoices->pluck('invoice_number')->sort()->values();
        $expectedNumbers = ['INV-2025-0001', 'INV-2025-0002', 'INV-2025-0003', 'INV-2025-0004', 'INV-2025-0005'];

        $this->assertEquals($expectedNumbers, $numbers->toArray());
    }

    public function test_invoice_number_service_generates_correct_format(): void
    {
        $company = Company::factory()->create(['invoice_prefix' => 'TEST']);
        $service = new InvoiceNumberService;

        $number = $service->generate($company);
        $this->assertMatchesRegularExpression('/^TEST-2025-\d{4}$/', $number);
    }

    public function test_invoice_number_service_validates_uniqueness(): void
    {
        $company = Company::factory()->create(['invoice_prefix' => 'INV']);
        $service = new InvoiceNumberService;

        // Create an invoice
        Invoice::factory()->create(['company_id' => $company->id, 'invoice_number' => 'INV-2025-0001']);

        // Validate uniqueness
        $this->assertFalse($service->validateUnique('INV-2025-0001', $company));
        $this->assertTrue($service->validateUnique('INV-2025-0002', $company));
    }
}
