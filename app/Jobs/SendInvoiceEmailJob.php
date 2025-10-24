<?php

namespace App\Jobs;

use App\Mail\InvoiceMailable;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice
    ) {
        //
    }

    public function handle(): void
    {
        // Send the invoice email with PDF attachment
        Mail::to($this->invoice->customer->email)
            ->send(new InvoiceMailable($this->invoice));
    }
}
