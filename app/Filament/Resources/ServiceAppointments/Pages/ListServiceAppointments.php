<?php

namespace App\Filament\Resources\ServiceAppointments\Pages;

use App\Filament\Resources\ServiceAppointments\ServiceAppointmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceAppointments extends ListRecords
{
    protected static string $resource = ServiceAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
