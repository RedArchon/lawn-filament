<?php

namespace App\Filament\Resources\Properties;

use App\Filament\Resources\Properties\Pages\CreateProperty;
use App\Filament\Resources\Properties\Pages\EditProperty;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Properties\RelationManagers\ServiceAppointmentsRelationManager;
use App\Filament\Resources\Properties\RelationManagers\ServiceSchedulesRelationManager;
use App\Filament\Resources\Properties\Schemas\PropertyForm;
use App\Filament\Resources\Properties\Tables\PropertiesTable;
use App\Models\Property;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static string|UnitEnum|null $navigationGroup = 'Customer Management';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return PropertyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ServiceSchedulesRelationManager::class,
            ServiceAppointmentsRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProperties::route('/'),
            'create' => CreateProperty::route('/create'),
            'edit' => EditProperty::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
