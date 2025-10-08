<?php

namespace App\Filament\Resources\Teams\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Team Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),
                        ColorPicker::make('color')
                            ->label('Team Color')
                            ->helperText('Choose a color to identify this team')
                            ->columnSpan(1),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(1),
                        TextInput::make('max_daily_appointments')
                            ->label('Max Daily Appointments')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->helperText('Maximum number of appointments this team can handle per day')
                            ->columnSpan(1),
                        TimePicker::make('start_time')
                            ->label('Start Time')
                            ->seconds(false)
                            ->default('08:00:00')
                            ->helperText('Default start time for this team')
                            ->columnSpan(1),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(4),

                Section::make('Team Members')
                    ->schema([
                        Select::make('users')
                            ->relationship('users', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Select users to assign to this team'),
                    ]),
            ]);
    }
}
