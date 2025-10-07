<?php

namespace App\Filament\Resources\ServiceAppointments\Pages;

use App\Filament\Resources\ServiceAppointments\ServiceAppointmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceAppointment extends EditRecord
{
    protected static string $resource = ServiceAppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
