<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice #{{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .invoice-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .invoice-details h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
        }
        .detail-item {
            margin-bottom: 10px;
        }
        .detail-label {
            font-weight: bold;
            color: #6c757d;
            font-size: 14px;
        }
        .detail-value {
            color: #2c3e50;
            font-size: 16px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th,
        .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        .total-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: right;
        }
        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .total-line.final {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            border-top: 2px solid #2c3e50;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
        .payment-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .payment-info h3 {
            margin-top: 0;
            color: #1976d2;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $company->name }}</div>
        <div style="color: #6c757d;">Professional Lawn Care Services</div>
    </div>

    <div class="invoice-details">
        <h2>Invoice #{{ $invoice->invoice_number }}</h2>
        <div class="details-grid">
            <div>
                <div class="detail-item">
                    <div class="detail-label">Bill To:</div>
                    <div class="detail-value">
                        <strong>{{ $customer->name }}</strong><br>
                        {{ $customer->email }}<br>
                        @if($customer->phone)
                            {{ $customer->phone }}
                        @endif
                    </div>
                </div>
            </div>
            <div>
                <div class="detail-item">
                    <div class="detail-label">Invoice Date:</div>
                    <div class="detail-value">{{ $invoice->invoice_date->format('M j, Y') }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Due Date:</div>
                    <div class="detail-value">{{ $invoice->due_date->format('M j, Y') }}</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Status:</div>
                    <div class="detail-value">
                        <span style="
                            background-color: {{ $invoice->status === 'sent' ? '#007bff' : '#28a745' }};
                            color: white;
                            padding: 4px 8px;
                            border-radius: 4px;
                            font-size: 12px;
                            text-transform: uppercase;
                        ">{{ ucfirst($invoice->status) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td>{{ $item->quantity }}</td>
                <td>${{ number_format($item->unit_price, 2) }}</td>
                <td>${{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-line">
            <span>Subtotal:</span>
            <span>${{ number_format($invoice->subtotal, 2) }}</span>
        </div>
        @if($invoice->tax_rate > 0)
        <div class="total-line">
            <span>Tax ({{ $invoice->tax_rate }}%):</span>
            <span>${{ number_format($invoice->tax_amount, 2) }}</span>
        </div>
        @endif
        <div class="total-line final">
            <span>Total:</span>
            <span>${{ number_format($invoice->total, 2) }}</span>
        </div>
    </div>

    @if($invoice->notes)
    <div style="margin-top: 30px;">
        <h3>Notes:</h3>
        <p style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">
            {{ $invoice->notes }}
        </p>
    </div>
    @endif

    <div class="payment-info">
        <h3>Payment Information</h3>
        <p>Thank you for your business! Please remit payment by the due date.</p>
        <p><strong>Due Date: {{ $invoice->due_date->format('M j, Y') }}</strong></p>
        @if($company->payment_instructions)
            <p>{{ $company->payment_instructions }}</p>
        @endif
    </div>

    <div class="footer">
        <p>This invoice was generated by {{ $company->name }} on {{ now()->format('M j, Y \a\t g:i A') }}.</p>
        <p>If you have any questions about this invoice, please contact us.</p>
    </div>
</body>
</html>
