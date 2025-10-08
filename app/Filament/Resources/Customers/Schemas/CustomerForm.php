<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Enums\State;
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
                            ->maxLength(255)
                            ->placeholder('John Doe')
                            ->helperText('Full name of the customer'),

                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('customer@example.com')
                            ->helperText('Primary email address for communication'),

                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('(555) 123-4567')
                            ->helperText('Primary contact phone number'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Additional Information')
                    ->schema([
                        TextInput::make('company_name')
                            ->maxLength(255)
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
                            ->maxLength(255)
                            ->placeholder('123 Main Street')
                            ->columnSpanFull(),

                        TextInput::make('billing_city')
                            ->maxLength(255)
                            ->placeholder('City'),

                        Select::make('billing_state')
                            ->options(State::options())
                            ->searchable()
                            ->placeholder('Select state'),

                        TextInput::make('billing_zip')
                            ->maxLength(255)
                            ->placeholder('12345')
                            ->mask('99999'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
