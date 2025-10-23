<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Models\ServiceAppointment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Invoice Details - Full Width
                Section::make('Invoice Details')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $customer = \App\Models\Customer::find($state);
                                    if ($customer && $customer->company) {
                                        $set('due_date', now()->addDays($customer->company->payment_terms_days ?? 30)->toDateString());
                                        $set('tax_rate', $customer->company->default_tax_rate ?? 0);
                                    }
                                }
                            }),
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('invoice_date')
                                    ->label('Invoice Date')
                                    ->default(now()->toDateString())
                                    ->required(),
                                DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->required(),
                            ]),
                        Grid::make(2)
                            ->schema([
                                TextInput::make('tax_rate')
                                    ->label('Tax Rate (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->reactive(),
                                Select::make('status')
                                    ->options([
                                        'draft' => 'Draft',
                                        'sent' => 'Sent',
                                        'paid' => 'Paid',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->default('draft')
                                    ->required(),
                            ]),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),
                    ])
                    ->columnSpanFull(),

                // Invoice Totals - Full Width
                Section::make('Invoice Totals')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Placeholder::make('subtotal')
                                    ->label('Subtotal')
                                    ->content(function (callable $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = 0;
                                        foreach ($items as $item) {
                                            $quantity = (float) ($item['quantity'] ?? 0);
                                            $unitPrice = (float) ($item['unit_price'] ?? 0);
                                            $subtotal += $quantity * $unitPrice;
                                        }

                                        return '$'.number_format($subtotal, 2);
                                    }),
                                Placeholder::make('tax_amount')
                                    ->label('Tax Amount')
                                    ->content(function (callable $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = 0;
                                        foreach ($items as $item) {
                                            $quantity = (float) ($item['quantity'] ?? 0);
                                            $unitPrice = (float) ($item['unit_price'] ?? 0);
                                            $subtotal += $quantity * $unitPrice;
                                        }
                                        $taxRate = (float) $get('tax_rate') ?: 0;
                                        $taxAmount = $subtotal * ($taxRate / 100);

                                        return '$'.number_format($taxAmount, 2);
                                    }),
                                Placeholder::make('total')
                                    ->label('Total')
                                    ->content(function (callable $get) {
                                        $items = $get('items') ?? [];
                                        $subtotal = 0;
                                        foreach ($items as $item) {
                                            $quantity = (float) ($item['quantity'] ?? 0);
                                            $unitPrice = (float) ($item['unit_price'] ?? 0);
                                            $subtotal += $quantity * $unitPrice;
                                        }
                                        $taxRate = (float) $get('tax_rate') ?: 0;
                                        $taxAmount = $subtotal * ($taxRate / 100);
                                        $total = $subtotal + $taxAmount;

                                        return '$'.number_format($total, 2);
                                    }),
                            ]),
                    ])
                    ->columnSpanFull(),

                // Full Width - Line Items
                Section::make('Line Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Grid::make(6)
                                    ->schema([
                                        Select::make('service_appointment_id')
                                            ->label('Service Appointment')
                                            ->relationship(
                                                name: 'serviceAppointment',
                                                titleAttribute: 'id',
                                                modifyQueryUsing: function ($query, $get) {
                                                    $customerId = $get('../../customer_id');
                                                    if ($customerId) {
                                                        return $query->whereHas('property', function ($q) use ($customerId) {
                                                            $q->where('customer_id', $customerId);
                                                        })->uninvoiced()
                                                        ->with(['serviceType', 'property']);
                                                    }

                                                    return $query->uninvoiced()
                                                        ->with(['serviceType', 'property']);
                                                }
                                            )
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->serviceType->name} - {$record->property->full_address}")
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                if ($state) {
                                                    $appointment = ServiceAppointment::with(['serviceType', 'property'])->find($state);
                                                    if ($appointment) {
                                                        $set('description', "{$appointment->serviceType->name} - {$appointment->property->full_address}");
                                                        $set('unit_price', $appointment->serviceType->default_price);
                                                    }
                                                }
                                            })
                                            ->columnSpan(2),
                                        TextInput::make('description')
                                            ->label('Description')
                                            ->required()
                                            ->columnSpan(2),
                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(0.01)
                                            ->reactive()
                                            ->columnSpan(1),
                                        TextInput::make('unit_price')
                                            ->label('Unit Price')
                                            ->numeric()
                                            ->prefix('$')
                                            ->required()
                                            ->reactive()
                                            ->columnSpan(1),
                                        Placeholder::make('line_total')
                                            ->label('Line Total')
                                            ->content(function (callable $get) {
                                                $quantity = (float) $get('quantity') ?: 0;
                                                $unitPrice = (float) $get('unit_price') ?: 0;

                                                return '$'.number_format($quantity * $unitPrice, 2);
                                            })
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->addActionLabel('Add Line Item')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['description'] ?? null),
                    ])
                    ->columnSpanFull(),

            ]);
    }
}
