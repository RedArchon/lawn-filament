<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceSchedules';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_type_id')
                    ->label('Service Type')
                    ->relationship('serviceType', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('frequency')
                    ->options([
                        'weekly' => 'Weekly',
                        'biweekly' => 'Bi-weekly',
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                    ])
                    ->required()
                    ->helperText('How often the service should be performed'),

                DatePicker::make('start_date')
                    ->required()
                    ->helperText('When to start generating appointments'),

                DatePicker::make('end_date')
                    ->helperText('Optional: When to stop generating appointments')
                    ->after('start_date'),

                TextInput::make('day_of_week')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(6)
                    ->helperText('0 = Sunday, 6 = Saturday'),

                TextInput::make('week_of_month')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(4)
                    ->helperText('For monthly frequency'),

                Toggle::make('is_active')
                    ->default(true)
                    ->required()
                    ->helperText('Only active schedules generate appointments'),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('frequency')
            ->columns([
                TextColumn::make('serviceType.name')
                    ->label('Service Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('frequency')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'weekly' => 'info',
                        'biweekly' => 'success',
                        'monthly' => 'warning',
                        'quarterly' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('start_date')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->date('M j, Y')
                    ->placeholder('Ongoing')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
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
