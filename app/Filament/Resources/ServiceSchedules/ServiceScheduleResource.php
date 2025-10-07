<?php

namespace App\Filament\Resources\ServiceSchedules;

use App\Filament\Resources\ServiceSchedules\Pages\CreateServiceSchedule;
use App\Filament\Resources\ServiceSchedules\Pages\EditServiceSchedule;
use App\Filament\Resources\ServiceSchedules\Pages\ListServiceSchedules;
use App\Filament\Resources\ServiceSchedules\Schemas\ServiceScheduleForm;
use App\Filament\Resources\ServiceSchedules\Tables\ServiceSchedulesTable;
use App\Models\ServiceSchedule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceScheduleResource extends Resource
{
    protected static ?string $model = ServiceSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ServiceScheduleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceSchedulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceSchedules::route('/'),
            'create' => CreateServiceSchedule::route('/create'),
            'edit' => EditServiceSchedule::route('/{record}/edit'),
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
