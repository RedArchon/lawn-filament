<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\State;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
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
                    ->collapsible(false),

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
                    ->collapsible(false),

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
                            ->dehydrated(false),
                    ])
                    ->columns(3)
                    ->collapsible(false),
            ]);
    }
}
