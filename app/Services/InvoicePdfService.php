<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoicePdfService
{
    public function generate(Invoice $invoice): \Barryvdh\DomPDF\PDF
    {
        $invoice->load(['customer', 'items', 'company']);

        return Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
        ])->setPaper('a4');
    }

    public function download(Invoice $invoice): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $pdf = $this->generate($invoice);
        $filename = "Invoice-{$invoice->invoice_number}.pdf";

        return $pdf->download($filename);
    }
}
