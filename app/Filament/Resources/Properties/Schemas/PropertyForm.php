<?php

namespace App\Filament\Resources\Properties\Schemas;

use App\Enums\State;
use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PropertyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Customer')
                    ->schema([
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                                Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                    ])
                                    ->default('active')
                                    ->required(),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return Customer::create($data)->getKey();
                            })
                            ->helperText('Select customer or create a new one'),
                    ])
                    ->collapsible(),

                Section::make('Property Address')
                    ->schema([
                        TextInput::make('address')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('123 Oak Street')
                            ->columnSpanFull(),

                        TextInput::make('city')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Springfield'),

                        Select::make('state')
                            ->options(State::options())
                            ->required()
                            ->searchable()
                            ->placeholder('Select state'),

                        TextInput::make('zip')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('12345')
                            ->mask('99999'),
                    ])
                    ->columns(3)
                    ->description('Address will be automatically geocoded after saving')
                    ->collapsible(),

                Section::make('Property Details')
                    ->schema([
                        TextInput::make('lot_size')
                            ->maxLength(255)
                            ->placeholder('0.25 acres')
                            ->helperText('e.g., "0.5 acres" or "5000 sq ft"'),

                        Textarea::make('access_instructions')
                            ->rows(3)
                            ->placeholder('Gate code, parking instructions, etc.')
                            ->helperText('Special instructions for accessing the property')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Service Status')
                    ->schema([
                        Select::make('service_status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'seasonal' => 'Seasonal',
                            ])
                            ->default('active')
                            ->required()
                            ->helperText('Current service status for this property'),
                    ])
                    ->columns(1),

                Section::make('Geocoding Information')
                    ->schema([
                        TextInput::make('latitude')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Automatically populated'),

                        TextInput::make('longitude')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Automatically populated'),

                        TextInput::make('geocoded_at')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Last geocoded date'),

                        Textarea::make('geocoding_error')
                            ->rows(2)
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record?->geocoding_failed ?? false)
                            ->helperText('Error details if geocoding failed')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record && ($record->latitude || $record->geocoding_failed))
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
