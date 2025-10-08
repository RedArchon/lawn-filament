<?php

namespace App\Filament\Resources\Properties\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServiceAppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceAppointments';

    protected static ?string $title = 'Service Appointments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_schedule_id')
                    ->label('Service Schedule')
                    ->relationship('serviceSchedule', 'id')
                    ->searchable()
                    ->preload()
                    ->helperText('Optional: Link to a recurring schedule'),

                Select::make('service_type_id')
                    ->label('Service Type')
                    ->relationship('serviceType', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Select::make('team_id')
                    ->label('Assigned Team')
                    ->relationship('team', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Optional: Assign to a team'),

                DatePicker::make('scheduled_date')
                    ->required()
                    ->helperText('Date the service is scheduled'),

                TimePicker::make('scheduled_time')
                    ->seconds(false)
                    ->helperText('Optional: Time the service is scheduled'),

                Select::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'skipped' => 'Skipped',
                    ])
                    ->default('scheduled')
                    ->required(),

                DateTimePicker::make('completed_at')
                    ->helperText('Automatically set when marked complete'),

                TextInput::make('duration_minutes')
                    ->numeric()
                    ->minValue(0)
                    ->helperText('How long the service took'),

                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('scheduled_date')
            ->columns([
                TextColumn::make('serviceType.name')
                    ->label('Service Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('scheduled_date')
                    ->label('Scheduled')
                    ->date('M j, Y')
                    ->sortable(),

                TextColumn::make('scheduled_time')
                    ->label('Time')
                    ->time('g:i A')
                    ->placeholder('Not set'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'info',
                        'in_progress' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        'skipped' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('team.name')
                    ->label('Team')
                    ->badge()
                    ->color(fn ($record) => $record->team?->color ?? 'gray')
                    ->placeholder('Unassigned'),

                TextColumn::make('completed_at')
                    ->label('Completed')
                    ->dateTime('M j, Y')
                    ->placeholder('Not completed')
                    ->toggleable(),
            ])
            ->defaultSort('scheduled_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'skipped' => 'Skipped',
                    ]),
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
