<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
        }
        .company-info h1 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .company-info p {
            margin: 2px 0;
            color: #6b7280;
        }
        .invoice-details {
            text-align: right;
        }
        .invoice-details h2 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 20px;
        }
        .invoice-details p {
            margin: 2px 0;
            color: #6b7280;
        }
        .bill-to {
            margin-bottom: 30px;
        }
        .bill-to h3 {
            color: #1f2937;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .bill-to p {
            margin: 2px 0;
            color: #6b7280;
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
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #1f2937;
        }
        .items-table .text-right {
            text-align: right;
        }
        .totals {
            margin-left: auto;
            width: 300px;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .totals .total-row {
            font-weight: 600;
            font-size: 18px;
            color: #1f2937;
            border-top: 2px solid #1f2937;
        }
        .notes {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .notes h3 {
            color: #1f2937;
            margin: 0 0 10px 0;
        }
        .notes p {
            color: #6b7280;
            margin: 0;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-draft { background-color: #f3f4f6; color: #374151; }
        .status-sent { background-color: #dbeafe; color: #1e40af; }
        .status-paid { background-color: #d1fae5; color: #065f46; }
        .status-overdue { background-color: #fee2e2; color: #991b1b; }
        .status-cancelled { background-color: #f3f4f6; color: #374151; }
        .status-refunded { background-color: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <h1>{{ $company->name }}</h1>
            @if($company->address)
                <p>{{ $company->address }}</p>
            @endif
            @if($company->city && $company->state && $company->zip)
                <p>{{ $company->city }}, {{ $company->state }} {{ $company->zip }}</p>
            @endif
            @if($company->phone)
                <p>Phone: {{ $company->phone }}</p>
            @endif
            @if($company->email)
                <p>Email: {{ $company->email }}</p>
            @endif
        </div>
        <div class="invoice-details">
            <h2>INVOICE</h2>
            <p><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Date:</strong> {{ $invoice->invoice_date->format('M j, Y') }}</p>
            <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M j, Y') }}</p>
            <p><strong>Status:</strong> 
                <span class="status status-{{ $invoice->status }}">{{ $invoice->statusLabel() }}</span>
            </p>
        </div>
    </div>

    <div class="bill-to">
        <h3>Bill To:</h3>
        <p><strong>{{ $customer->name }}</strong></p>
        @if($customer->company_name)
            <p>{{ $customer->company_name }}</p>
        @endif
        @if($customer->billing_address)
            <p>{{ $customer->billing_address }}</p>
        @endif
        @if($customer->billing_city && $customer->billing_state && $customer->billing_zip)
            <p>{{ $customer->billing_city }}, {{ $customer->billing_state }} {{ $customer->billing_zip }}</p>
        @endif
        @if($customer->email)
            <p>Email: {{ $customer->email }}</p>
        @endif
        @if($customer->phone)
            <p>Phone: {{ $customer->phone }}</p>
        @endif
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">${{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            @if($invoice->tax_rate > 0)
                <tr>
                    <td>Tax ({{ number_format($invoice->tax_rate, 1) }}%):</td>
                    <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td class="text-right">${{ number_format($invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
        <div class="notes">
            <h3>Notes</h3>
            <p>{{ $invoice->notes }}</p>
        </div>
    @endif

    @if($company->payment_terms_days)
        <div class="notes">
            <h3>Payment Terms</h3>
            <p>Payment is due within {{ $company->payment_terms_days }} days of invoice date.</p>
        </div>
    @endif
</body>
</html>
