<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Enums\SchedulingType;
use App\Enums\ServiceFrequency;
use App\Filament\Resources\ServiceSchedules\ServiceScheduleResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceSchedules';

    protected static ?string $relatedResource = ServiceScheduleResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->schema([
                        Select::make('property_id')
                            ->relationship('property', 'address', fn ($query) => $query->where('customer_id', $this->getOwnerRecord()->id))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select which property this service is for')
                            ->columnSpan(2),
                        Select::make('service_type_id')
                            ->relationship('serviceType', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),
                    ])
                    ->columns(5)
                    ->collapsible(false),

                Section::make('Scheduling Configuration')
                    ->schema([
                        Radio::make('scheduling_type')
                            ->label('Schedule Type')
                            ->options(SchedulingType::options())
                            ->descriptions([
                                SchedulingType::Manual->value => SchedulingType::Manual->getDescription(),
                                SchedulingType::Recurring->value => SchedulingType::Recurring->getDescription(),
                                SchedulingType::Seasonal->value => SchedulingType::Seasonal->getDescription(),
                            ])
                            ->default(SchedulingType::Recurring)
                            ->required()
                            ->reactive()
                            ->columnSpanFull(),

                        // Manual Schedule Fields
                        DatePicker::make('start_date')
                            ->label('Service Date')
                            ->required()
                            ->visible(fn (Get $get) => $get('scheduling_type') === SchedulingType::Manual->value)
                            ->columnSpan(2),

                        // Recurring Schedule Fields
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->visible(fn (Get $get) => $get('scheduling_type') === SchedulingType::Recurring->value)
                            ->columnSpan(1),
                        DatePicker::make('end_date')
                            ->label('End Date (Optional)')
                            ->visible(fn (Get $get) => $get('scheduling_type') === SchedulingType::Recurring->value)
                            ->columnSpan(1),
                        Select::make('frequency')
                            ->label('Frequency')
                            ->options([
                                'weekly' => 'Weekly',
                                'biweekly' => 'Biweekly',
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                            ])
                            ->required()
                            ->visible(fn (Get $get) => $get('scheduling_type') === SchedulingType::Recurring->value)
                            ->columnSpan(1),
                        Select::make('day_of_week')
                            ->label('Day of Week')
                            ->options([
                                0 => 'Sunday',
                                1 => 'Monday',
                                2 => 'Tuesday',
                                3 => 'Wednesday',
                                4 => 'Thursday',
                                5 => 'Friday',
                                6 => 'Saturday',
                            ])
                            ->visible(fn (Get $get) => in_array($get('frequency'), ['weekly', 'biweekly']))
                            ->helperText('Select the day of week for the service')
                            ->columnSpan(1),
                        TextInput::make('week_of_month')
                            ->label('Week of Month')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(4)
                            ->visible(fn (Get $get) => $get('frequency') === 'monthly')
                            ->helperText('1 = First week, 2 = Second week, etc.')
                            ->columnSpan(1),

                        // Seasonal Schedule Fields
                        DatePicker::make('start_date')
                            ->label('Schedule Start Date')
                            ->required()
                            ->visible(fn (Get $get) => $get('scheduling_type') === SchedulingType::Seasonal->value)
                            ->columnSpan(1),
                        DatePicker::make('end_date')
                            ->label('Schedule End Date (Optional)')
                            ->visible(fn (Get $get) => $get('scheduling_type') === SchedulingType::Seasonal->value)
                            ->helperText('Leave empty for ongoing service')
                            ->columnSpan(1),
                    ])
                    ->columns(4),

                Section::make('Seasonal Frequency Periods')
                    ->description('Define different frequencies for different times of the year')
                    ->schema([
                        Repeater::make('seasonalPeriods')
                            ->relationship()
                            ->schema([
                                Select::make('start_month')
                                    ->label('Start Month')
                                    ->options([
                                        1 => 'January',
                                        2 => 'February',
                                        3 => 'March',
                                        4 => 'April',
                                        5 => 'May',
                                        6 => 'June',
                                        7 => 'July',
                                        8 => 'August',
                                        9 => 'September',
                                        10 => 'October',
                                        11 => 'November',
                                        12 => 'December',
                                    ])
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('start_day')
                                    ->label('Day')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->required()
                                    ->columnSpan(1),
                                Select::make('end_month')
                                    ->label('End Month')
                                    ->options([
                                        1 => 'January',
                                        2 => 'February',
                                        3 => 'March',
                                        4 => 'April',
                                        5 => 'May',
                                        6 => 'June',
                                        7 => 'July',
                                        8 => 'August',
                                        9 => 'September',
                                        10 => 'October',
                                        11 => 'November',
                                        12 => 'December',
                                    ])
                                    ->required()
                                    ->columnSpan(1),
                                TextInput::make('end_day')
                                    ->label('Day')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(31)
                                    ->required()
                                    ->columnSpan(1),
                                Select::make('frequency')
                                    ->label('Frequency')
                                    ->options(ServiceFrequency::options())
                                    ->required()
                                    ->columnSpan(2),
                                Textarea::make('notes')
                                    ->label('Period Notes')
                                    ->rows(2)
                                    ->placeholder('e.g., Heavy growth period')
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->addActionLabel('Add Seasonal Period')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Get $get) => $get('scheduling_type') === SchedulingType::Seasonal->value)
                    ->collapsible(false),

                Section::make('Additional Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('property.address')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serviceType.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('scheduling_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->sortable(),
                TextColumn::make('frequency')
                    ->label('Frequency')
                    ->formatStateUsing(fn ($state) => $state ? ucfirst(str_replace('_', ' ', $state)) : 'Seasonal')
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->placeholder('Ongoing')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('appointments_count')
                    ->label('Appointments')
                    ->counts('appointments')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('scheduling_type')
                    ->label('Schedule Type')
                    ->options(SchedulingType::options())
                    ->multiple(),
                SelectFilter::make('property_id')
                    ->label('Property')
                    ->relationship('property', 'address')
                    ->searchable()
                    ->preload()
                    ->multiple(),
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
            ])
            ->defaultSort('start_date', 'desc');
    }
}
