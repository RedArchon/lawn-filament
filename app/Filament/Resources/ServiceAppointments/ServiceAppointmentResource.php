<?php

namespace App\Filament\Resources\ServiceAppointments;

use App\Filament\Resources\ServiceAppointments\Pages\CreateServiceAppointment;
use App\Filament\Resources\ServiceAppointments\Pages\EditServiceAppointment;
use App\Filament\Resources\ServiceAppointments\Pages\ListServiceAppointments;
use App\Filament\Resources\ServiceAppointments\Schemas\ServiceAppointmentForm;
use App\Filament\Resources\ServiceAppointments\Tables\ServiceAppointmentsTable;
use App\Models\ServiceAppointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceAppointmentResource extends Resource
{
    protected static ?string $model = ServiceAppointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ServiceAppointmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServiceAppointmentsTable::configure($table);
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
            'index' => ListServiceAppointments::route('/'),
            'create' => CreateServiceAppointment::route('/create'),
            'edit' => EditServiceAppointment::route('/{record}/edit'),
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
