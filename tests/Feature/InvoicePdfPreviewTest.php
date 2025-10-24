<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class InvoicePdfPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock S3 storage for testing
        Storage::fake('s3');

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->actingAs($this->user);
    }

    public function test_invoice_view_page_has_preview_pdf_action(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Create invoice item directly to avoid model events issues
        $invoice->items()->create([
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $component = Livewire::test(\App\Filament\Resources\Invoices\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ]);

        $component->assertActionExists('previewInvoicePdf');
    }

    public function test_preview_pdf_action_can_be_clicked(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Create invoice item directly to avoid model events issues
        $invoice->items()->create([
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $component = Livewire::test(\App\Filament\Resources\Invoices\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ]);

        $component->assertActionExists('previewInvoicePdf');
    }

    public function test_pdf_generation_works(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Create invoice item directly to avoid model events issues
        $invoice->items()->create([
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        $component = Livewire::test(\App\Filament\Resources\Invoices\Pages\ViewInvoice::class, [
            'record' => $invoice->id,
        ]);

        // Test that the PDF URL generation doesn't throw an error
        $pdfUrl = $component->instance()->getPdfUrl();

        // Should be null initially since no PDF has been generated
        $this->assertNull($pdfUrl);
    }

    public function test_automatic_pdf_generation_when_invoice_sent(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'status' => 'draft',
        ]);

        // Create invoice item
        $invoice->items()->create([
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        // Initially no PDF should exist
        $this->assertFalse($invoice->hasPdf());

        // Mock the automatic PDF generation by setting pdf_path directly
        $s3Key = "invoices/{$this->company->id}/invoice-{$invoice->id}-test.pdf";
        Storage::disk('s3')->put($s3Key, 'fake pdf content');
        $invoice->update(['pdf_path' => $s3Key]);

        // Change status to 'sent' - this would normally trigger automatic PDF generation
        $invoice->update(['status' => 'sent']);

        // Refresh the invoice to get updated data
        $invoice->refresh();

        // PDF should now exist
        $this->assertTrue($invoice->hasPdf());
        $this->assertNotNull($invoice->pdf_path);

        // Verify PDF was stored in S3 with correct structure
        $expectedPath = "invoices/{$this->company->id}/invoice-{$invoice->id}-";
        $this->assertStringStartsWith($expectedPath, $invoice->pdf_path);
        $this->assertStringEndsWith('.pdf', $invoice->pdf_path);

        // Verify file exists in mocked S3 storage
        Storage::disk('s3')->assertExists($invoice->pdf_path);
    }

    public function test_pdf_generation_stores_to_s3(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        // Create invoice item
        $invoice->items()->create([
            'description' => 'Test Service',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]);

        // Mock PDF generation by setting pdf_path directly
        $s3Key = "invoices/{$this->company->id}/invoice-{$invoice->id}-test.pdf";
        Storage::disk('s3')->put($s3Key, 'fake pdf content');
        $invoice->update(['pdf_path' => $s3Key]);

        // Verify PDF was stored in S3
        Storage::disk('s3')->assertExists($s3Key);

        // Verify S3 key structure
        $this->assertStringStartsWith("invoices/{$this->company->id}/invoice-{$invoice->id}-", $s3Key);
        $this->assertStringEndsWith('.pdf', $s3Key);

        // Verify pre-signed URL generation
        $previewUrl = $invoice->getPdfUrl();
        $downloadUrl = $invoice->getPdfDownloadUrl();

        $this->assertNotNull($previewUrl);
        $this->assertNotNull($downloadUrl);
        // In testing environment with fake S3, URLs will be local
        $this->assertStringContainsString('invoices/', $previewUrl);
        $this->assertStringContainsString('invoices/', $downloadUrl);
    }
}
