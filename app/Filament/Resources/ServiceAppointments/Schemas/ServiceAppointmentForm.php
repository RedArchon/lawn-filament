<?php

namespace App\Filament\Resources\ServiceAppointments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class ServiceAppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('service_schedule_id')
                    ->relationship('serviceSchedule', 'id'),
                Select::make('property_id')
                    ->relationship('property', 'id')
                    ->required(),
                Select::make('service_type_id')
                    ->relationship('serviceType', 'name')
                    ->required(),
                Select::make('team_id')
                    ->relationship('team', 'name')
                    ->label('Assigned Team')
                    ->searchable()
                    ->preload(),
                DatePicker::make('scheduled_date')
                    ->required(),
                TimePicker::make('scheduled_time'),
                TextInput::make('status')
                    ->required()
                    ->default('scheduled'),
                DateTimePicker::make('completed_at'),
                TextInput::make('completed_by')
                    ->numeric(),
                TextInput::make('duration_minutes')
                    ->numeric(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
