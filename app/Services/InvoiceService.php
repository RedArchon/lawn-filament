<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ServiceAppointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function createFromAppointments(
        Company $company,
        Customer $customer,
        array $appointmentIds
    ): Invoice {
        return DB::transaction(function () use ($company, $customer, $appointmentIds) {
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays($company->payment_terms_days ?? 30)->toDateString(),
                'status' => 'draft',
                'tax_rate' => $company->default_tax_rate ?? 0,
            ]);

            foreach ($appointmentIds as $appointmentId) {
                $appointment = ServiceAppointment::findOrFail($appointmentId);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_appointment_id' => $appointment->id,
                    'description' => "{$appointment->serviceType->name} - {$appointment->property->full_address}",
                    'quantity' => 1,
                    'unit_price' => $appointment->serviceType->default_price,
                ]);

                $appointment->update(['invoiced_at' => now()]);
            }

            $this->calculateTotals($invoice);

            return $invoice;
        });
    }

    public function calculateTotals(Invoice $invoice): void
    {
        $invoice->load('items');

        $subtotal = $invoice->items->sum('line_total');
        $taxAmount = $subtotal * ($invoice->tax_rate / 100);
        $total = $subtotal + $taxAmount;

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    public function markPaid(Invoice $invoice, ?Carbon $paidAt = null): void
    {
        $invoice->update([
            'status' => 'paid',
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    public function send(Invoice $invoice): void
    {
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function cancel(Invoice $invoice, ?string $reason = null): void
    {
        $invoice->update([
            'status' => 'cancelled',
            'notes' => $invoice->notes ? $invoice->notes."\n\nCancelled: ".$reason : 'Cancelled: '.$reason,
        ]);
    }
}
