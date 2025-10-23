<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    public function generate(Company $company): string
    {
        return DB::transaction(function () use ($company) {
            $year = now()->year;
            $prefix = $company->invoice_prefix ?? 'INV';

            // Get last invoice number for this company/year
            $lastInvoice = Invoice::where('company_id', $company->id)
                ->where('invoice_number', 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('invoice_number')
                ->first();

            // Extract and increment number
            $nextNumber = 1;
            if ($lastInvoice) {
                preg_match('/-(\d+)$/', $lastInvoice->invoice_number, $matches);
                $nextNumber = ((int) $matches[1]) + 1;
            }

            return sprintf('%s-%d-%04d', $prefix, $year, $nextNumber);
        });
    }

    public function validateUnique(string $invoiceNumber, Company $company): bool
    {
        return ! Invoice::where('company_id', $company->id)
            ->where('invoice_number', $invoiceNumber)
            ->exists();
    }
}
