<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'service_appointment_id',
        'description',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            // Auto-calculate line_total
            $item->line_total = $item->quantity * $item->unit_price;
        });

        static::saved(function (InvoiceItem $item) {
            // Recalculate parent invoice totals
            $item->invoice->load('items');
            $subtotal = $item->invoice->items->sum('line_total');
            $taxAmount = $subtotal * ($item->invoice->tax_rate / 100);
            $total = $subtotal + $taxAmount;

            $item->invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ]);
        });

        static::deleted(function (InvoiceItem $item) {
            // Recalculate parent invoice totals after deletion
            $item->invoice->load('items');
            $subtotal = $item->invoice->items->sum('line_total');
            $taxAmount = $subtotal * ($item->invoice->tax_rate / 100);
            $total = $subtotal + $taxAmount;

            $item->invoice->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total' => $total,
            ]);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function serviceAppointment(): BelongsTo
    {
        return $this->belongsTo(ServiceAppointment::class);
    }
}
