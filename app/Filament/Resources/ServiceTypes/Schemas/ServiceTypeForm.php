<?php

namespace App\Filament\Resources\ServiceTypes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ServiceTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('default_duration_minutes')
                    ->required()
                    ->numeric(),
                TextInput::make('default_price')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
