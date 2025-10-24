<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invoice #{$this->invoice->invoice_number} from {$this->invoice->company->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
            with: [
                'invoice' => $this->invoice,
                'customer' => $this->invoice->customer,
                'company' => $this->invoice->company,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        // Attach PDF if it exists
        if ($this->invoice->hasPdf()) {
            try {
                $pdfContent = Storage::disk('s3')->get($this->invoice->pdf_path);
                $attachments[] = Attachment::fromData(
                    $pdfContent,
                    "Invoice-{$this->invoice->invoice_number}.pdf",
                    ['mime' => 'application/pdf']
                );
            } catch (\Exception $e) {
                // If S3 fails, try local storage
                try {
                    $pdfContent = Storage::disk('public')->get($this->invoice->pdf_path);
                    $attachments[] = Attachment::fromData(
                        $pdfContent,
                        "Invoice-{$this->invoice->invoice_number}.pdf",
                        ['mime' => 'application/pdf']
                    );
                } catch (\Exception $e) {
                    // PDF not available, continue without attachment
                }
            }
        }

        return $attachments;
    }
}
