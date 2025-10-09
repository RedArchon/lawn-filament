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
                        static::getNameFormField(),
                        static::getEmailFormField(),
                        static::getPhoneFormField(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Additional Information')
                    ->schema([
                        static::getCompanyNameFormField(),
                        static::getStatusFormField(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),

                Section::make('Billing Address')
                    ->schema([
                        static::getBillingAddressFormField(),
                        static::getBillingCityFormField(),
                        static::getBillingStateFormField(),
                        static::getBillingZipFormField(),
                        static::getServiceBillingAddressFormField(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getNameFormField(): TextInput
    {
        return TextInput::make('name')
            ->required()
            ->minLength(2)
            ->maxLength(255)
            ->placeholder('John Doe')
            ->helperText('Full name of the customer');
    }

    public static function getEmailFormField(): TextInput
    {
        return TextInput::make('email')
            ->required()
            ->email()
            ->unique('customers', 'email', ignoreRecord: true)
            ->maxLength(255)
            ->placeholder('customer@example.com')
            ->helperText('Primary email address for communication');
    }

    public static function getPhoneFormField(): TextInput
    {
        return TextInput::make('phone')
            ->required()
            ->tel()
            ->regex('/^[\d\s\-\(\)\+]+$/')
            ->maxLength(255)
            ->placeholder('(555) 123-4567')
            ->helperText('Primary contact phone number');
    }

    public static function getCompanyNameFormField(): TextInput
    {
        return TextInput::make('company_name')
            ->minLength(2)
            ->maxLength(255)
            ->nullable()
            ->placeholder('Company Name LLC')
            ->helperText('Optional: If customer is a business')
            ->columnSpan(2);
    }

    public static function getStatusFormField(): Select
    {
        return Select::make('status')
            ->options([
                'active' => 'Active',
                'inactive' => 'Inactive',
            ])
            ->default('active')
            ->required()
            ->helperText('Customer account status');
    }

    public static function getBillingAddressFormField(): TextInput
    {
        return TextInput::make('billing_address')
            ->minLength(5)
            ->maxLength(255)
            ->nullable()
            ->requiredIf('service_billing_address', true)
            ->placeholder('123 Main Street')
            ->columnSpanFull();
    }

    public static function getBillingCityFormField(): TextInput
    {
        return TextInput::make('billing_city')
            ->minLength(2)
            ->maxLength(255)
            ->nullable()
            ->requiredIf('service_billing_address', true)
            ->placeholder('City');
    }

    public static function getBillingStateFormField(): Select
    {
        return Select::make('billing_state')
            ->options(State::options())
            ->searchable()
            ->nullable()
            ->requiredIf('service_billing_address', true)
            ->placeholder('Select state');
    }

    public static function getBillingZipFormField(): TextInput
    {
        return TextInput::make('billing_zip')
            ->regex('/^\d{5}(-\d{4})?$/')
            ->maxLength(255)
            ->nullable()
            ->requiredIf('service_billing_address', true)
            ->placeholder('12345')
            ->mask('99999');
    }

    public static function getServiceBillingAddressFormField(): Checkbox
    {
        return Checkbox::make('service_billing_address')
            ->label('Use this address for property service')
            ->helperText('Check this to create a property record at this address')
            ->visible(true)
            ->columnSpanFull()
            ->dehydrated(false);
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
