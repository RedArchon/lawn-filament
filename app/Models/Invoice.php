<?php

namespace App\Models;

use App\Contracts\BelongsToCompany as BelongsToCompanyContract;
use App\Services\InvoiceNumberService;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model implements BelongsToCompanyContract
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'notes',
        'pdf_path',
        'sent_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = app(InvoiceNumberService::class)
                    ->generate($invoice->company);
            }
        });

        static::updated(function (Invoice $invoice) {
            // Generate PDF when invoice status changes to 'sent'
            // Disabled for testing to avoid memory issues
            if ($invoice->wasChanged('status') && $invoice->status === 'sent' && ! app()->environment('testing')) {
                $invoice->generatePdf();
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopeDraft(Builder $query): void
    {
        $query->where('status', 'draft');
    }

    public function scopeSent(Builder $query): void
    {
        $query->where('status', 'sent');
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', 'paid');
    }

    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', 'sent')
            ->where('due_date', '<', now()->toDateString());
    }

    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'sent' && $this->due_date < now()->toDateString();
    }

    public function formattedInvoiceNumber(): string
    {
        return $this->invoice_number;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'sent' => 'Sent',
            'paid' => 'Paid',
            'overdue' => 'Overdue',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst($this->status),
        };
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'sent' => 'blue',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'gray',
            'refunded' => 'warning',
            default => 'gray',
        };
    }

    public function hasPdf(): bool
    {
        if (empty($this->pdf_path)) {
            return false;
        }

        try {
            return Storage::disk('s3')->exists($this->pdf_path);
        } catch (\Exception $e) {
            // If S3 is not configured, fall back to checking if the path exists
            // This handles the case where AWS credentials are not set
            return ! empty($this->pdf_path);
        }
    }

    public function generatePdf(): string
    {
        $pdfService = app(\App\Services\InvoicePdfService::class);
        $pdf = $pdfService->generate($this);

        // Create S3 key with company structure
        $s3Key = "invoices/{$this->company_id}/invoice-{$this->id}-".time().'.pdf';

        try {
            // Store PDF in S3 with private visibility
            Storage::disk('s3')->put($s3Key, $pdf->output(), [
                'visibility' => 'private',
                'ContentType' => 'application/pdf',
            ]);

            // Update the model with the S3 key
            $this->update(['pdf_path' => $s3Key]);

            return $s3Key;
        } catch (\Exception $e) {
            // If S3 is not configured, fall back to local storage
            $localPath = "invoices/{$this->company_id}/invoice-{$this->id}-".time().'.pdf';

            Storage::disk('public')->put($localPath, $pdf->output());

            // Update the model with the local path
            $this->update(['pdf_path' => $localPath]);

            return $localPath;
        }
    }

    public function getPdfUrl(): ?string
    {
        if (! $this->hasPdf()) {
            return null;
        }

        try {
            // Try S3 first - generate pre-signed URL for secure access (valid for 1 hour)
            return Storage::disk('s3')->temporaryUrl($this->pdf_path, now()->addHour());
        } catch (\Exception $e) {
            // Fall back to local storage URL
            return Storage::disk('public')->url($this->pdf_path);
        }
    }

    public function getPdfDownloadUrl(): ?string
    {
        if (! $this->hasPdf()) {
            return null;
        }

        try {
            // Try S3 first - generate pre-signed URL with download disposition
            return Storage::disk('s3')->temporaryUrl($this->pdf_path, now()->addHour(), [
                'ResponseContentDisposition' => 'attachment; filename="Invoice-'.$this->invoice_number.'.pdf"',
            ]);
        } catch (\Exception $e) {
            // Fall back to local storage URL
            return Storage::disk('public')->url($this->pdf_path);
        }
    }
}
