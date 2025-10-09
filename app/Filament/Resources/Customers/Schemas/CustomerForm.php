<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\State;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contact Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->placeholder('John Doe')
                            ->helperText('Full name of the customer'),

                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->unique('customers', 'email', ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('customer@example.com')
                            ->helperText('Primary email address for communication'),

                        TextInput::make('phone')
                            ->required()
                            ->tel()
                            ->regex('/^[\d\s\-\(\)\+]+$/')
                            ->maxLength(255)
                            ->placeholder('(555) 123-4567')
                            ->helperText('Primary contact phone number'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Additional Information')
                    ->schema([
                        TextInput::make('company_name')
                            ->minLength(2)
                            ->maxLength(255)
                            ->nullable()
                            ->placeholder('Company Name LLC')
                            ->helperText('Optional: If customer is a business')
                            ->columnSpan(2),

                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required()
                            ->helperText('Customer account status'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Section::make('Billing Address')
                    ->schema([
                        TextInput::make('billing_address')
                            ->minLength(5)
                            ->maxLength(255)
                            ->nullable()
                            ->requiredIf('service_billing_address', true)
                            ->placeholder('123 Main Street')
                            ->columnSpanFull(),

                        TextInput::make('billing_city')
                            ->minLength(2)
                            ->maxLength(255)
                            ->nullable()
                            ->requiredIf('service_billing_address', true)
                            ->placeholder('City'),

                        Select::make('billing_state')
                            ->options(State::options())
                            ->searchable()
                            ->nullable()
                            ->requiredIf('service_billing_address', true)
                            ->placeholder('Select state'),

                        TextInput::make('billing_zip')
                            ->regex('/^\d{5}(-\d{4})?$/')
                            ->maxLength(255)
                            ->nullable()
                            ->requiredIf('service_billing_address', true)
                            ->placeholder('12345')
                            ->mask('99999'),

                        Checkbox::make('service_billing_address')
                            ->label('Use this address for property service')
                            ->helperText('Check this to create a property record at this address')
                            ->visible(true)
                            ->columnSpanFull()
                            ->dehydrated(false)
                            ->live()
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if (! $state || ! $get('billing_address') || ! $get('billing_city') || ! $get('billing_state') || ! $get('billing_zip')) {
                                    return;
                                }

                                $billingAddress = $get('billing_address');
                                $billingCity = $get('billing_city');
                                $billingState = $get('billing_state');
                                $billingZip = $get('billing_zip');

                                // Check if this billing address is already in the properties
                                $existingProperties = $get('properties') ?? [];
                                foreach ($existingProperties as $property) {
                                    if (
                                        ($property['address'] ?? null) === $billingAddress &&
                                        ($property['city'] ?? null) === $billingCity &&
                                        ($property['state'] ?? null) === $billingState &&
                                        ($property['zip'] ?? null) === $billingZip
                                    ) {
                                        return; // Already exists, don't add duplicate
                                    }
                                }

                                // Add the billing address as a new property
                                $newProperty = [
                                    'address' => $billingAddress,
                                    'city' => $billingCity,
                                    'state' => $billingState,
                                    'zip' => $billingZip,
                                    'service_status' => 'active',
                                    'lot_size' => null,
                                    'access_instructions' => null,
                                ];

                                $updatedProperties = array_merge($existingProperties, [$newProperty]);
                                $set('properties', $updatedProperties);
                            }),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    private function prePopulatePropertiesRepeater(callable $get, callable $set): void
    {
        $billingAddress = $get('billing_address');
        $billingCity = $get('billing_city');
        $billingState = $get('billing_state');
        $billingZip = $get('billing_zip');

        // Check if this billing address is already in the properties
        $existingProperties = $get('properties') ?? [];
        foreach ($existingProperties as $property) {
            if (
                ($property['address'] ?? null) === $billingAddress &&
                ($property['city'] ?? null) === $billingCity &&
                ($property['state'] ?? null) === $billingState &&
                ($property['zip'] ?? null) === $billingZip
            ) {
                return; // Already exists, don't add duplicate
            }
        }

        // Add the billing address as a new property
        $newProperty = [
            'address' => $billingAddress,
            'city' => $billingCity,
            'state' => $billingState,
            'zip' => $billingZip,
            'service_status' => 'active',
            'lot_size' => null,
            'access_instructions' => null,
        ];

        $updatedProperties = array_merge($existingProperties, [$newProperty]);
        $set('properties', $updatedProperties);
    }

    public static function getPropertiesRepeaterField(): Repeater
    {
        return Repeater::make('properties')
            ->label('Properties')
            ->schema([
                Section::make()
                    ->schema([
                        TextInput::make('address')
                            ->label('Address')
                            ->required()
                            ->minLength(5)
                            ->maxLength(255)
                            ->placeholder('123 Oak Street')
                            ->columnSpanFull(),

                        TextInput::make('city')
                            ->label('City')
                            ->required()
                            ->minLength(2)
                            ->maxLength(255)
                            ->placeholder('Springfield'),

                        Select::make('state')
                            ->label('State')
                            ->options(State::options())
                            ->required()
                            ->searchable()
                            ->placeholder('Select state'),

                        TextInput::make('zip')
                            ->label('ZIP Code')
                            ->required()
                            ->regex('/^\d{5}(-\d{4})?$/')
                            ->maxLength(255)
                            ->placeholder('12345')
                            ->mask('99999'),

                        TextInput::make('lot_size')
                            ->label('Lot Size')
                            ->maxLength(255)
                            ->nullable()
                            ->placeholder('0.25 acres')
                            ->helperText('e.g., "0.5 acres" or "5000 sq ft"'),

                        Select::make('service_status')
                            ->label('Service Status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'seasonal' => 'Seasonal',
                            ])
                            ->default('active')
                            ->required()
                            ->helperText('Current service status for this property'),

                        Textarea::make('access_instructions')
                            ->label('Access Instructions')
                            ->rows(3)
                            ->maxLength(1000)
                            ->nullable()
                            ->placeholder('Gate code, parking instructions, etc.')
                            ->helperText('Special instructions for accessing the property')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ])
            ->addActionLabel('Add Property')
            ->defaultItems(0)
            ->collapsible()
            ->itemLabel(fn (array $state): ?string => $state['address'] ?? null)
            ->helperText('Add properties for this customer. If you checked "Use billing address for service" in the previous step, that property will be pre-populated here.');
    }
}
