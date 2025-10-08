<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                            ->options([
                                'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
                                'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
                                'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
                                'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
                                'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
                                'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
                                'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
                                'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
                                'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
                                'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
                                'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
                                'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
                                'WI' => 'Wisconsin', 'WY' => 'Wyoming',
                            ])
                            ->required()
                            ->searchable()
                            ->placeholder('Select state'),

                        TextInput::make('zip')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('12345')
                            ->mask('99999'),
                    ])
                    ->columns(3),

                Section::make('Property Details')
                    ->schema([
                        TextInput::make('lot_size')
                            ->maxLength(255)
                            ->placeholder('0.25 acres'),

                        Textarea::make('access_instructions')
                            ->rows(3)
                            ->placeholder('Gate code, parking instructions, etc.')
                            ->columnSpanFull(),

                        Select::make('service_status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'seasonal' => 'Seasonal',
                            ])
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address')
            ->columns([
                TextColumn::make('address')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('city')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('state')
                    ->searchable(),

                TextColumn::make('service_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'seasonal' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('geocoded')
                    ->label('Geocoded')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->latitude && $record->longitude && ! $record->geocoding_failed),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
