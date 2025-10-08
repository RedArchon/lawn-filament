<?php

namespace App\Filament\Resources\Customers\Schemas;

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
                            ->helperText('Full name of the customer')
                            ->columnSpan(2),

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
                    ->columns(2)
                    ->collapsible(),

                Section::make('Company Details')
                    ->schema([
                        TextInput::make('company_name')
                            ->maxLength(255)
                            ->placeholder('Company Name LLC')
                            ->helperText('Optional: If customer is a business'),
                    ])
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
                            ->options([
                                'AL' => 'Alabama',
                                'AK' => 'Alaska',
                                'AZ' => 'Arizona',
                                'AR' => 'Arkansas',
                                'CA' => 'California',
                                'CO' => 'Colorado',
                                'CT' => 'Connecticut',
                                'DE' => 'Delaware',
                                'FL' => 'Florida',
                                'GA' => 'Georgia',
                                'HI' => 'Hawaii',
                                'ID' => 'Idaho',
                                'IL' => 'Illinois',
                                'IN' => 'Indiana',
                                'IA' => 'Iowa',
                                'KS' => 'Kansas',
                                'KY' => 'Kentucky',
                                'LA' => 'Louisiana',
                                'ME' => 'Maine',
                                'MD' => 'Maryland',
                                'MA' => 'Massachusetts',
                                'MI' => 'Michigan',
                                'MN' => 'Minnesota',
                                'MS' => 'Mississippi',
                                'MO' => 'Missouri',
                                'MT' => 'Montana',
                                'NE' => 'Nebraska',
                                'NV' => 'Nevada',
                                'NH' => 'New Hampshire',
                                'NJ' => 'New Jersey',
                                'NM' => 'New Mexico',
                                'NY' => 'New York',
                                'NC' => 'North Carolina',
                                'ND' => 'North Dakota',
                                'OH' => 'Ohio',
                                'OK' => 'Oklahoma',
                                'OR' => 'Oregon',
                                'PA' => 'Pennsylvania',
                                'RI' => 'Rhode Island',
                                'SC' => 'South Carolina',
                                'SD' => 'South Dakota',
                                'TN' => 'Tennessee',
                                'TX' => 'Texas',
                                'UT' => 'Utah',
                                'VT' => 'Vermont',
                                'VA' => 'Virginia',
                                'WA' => 'Washington',
                                'WV' => 'West Virginia',
                                'WI' => 'Wisconsin',
                                'WY' => 'Wyoming',
                            ])
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

                Section::make('Status')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required()
                            ->helperText('Customer account status'),
                    ])
                    ->columns(1),
            ]);
    }
}
