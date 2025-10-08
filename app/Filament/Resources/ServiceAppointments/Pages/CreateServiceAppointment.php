<?php

namespace App\Filament\Resources\ServiceAppointments\Pages;

use App\Filament\Resources\ServiceAppointments\ServiceAppointmentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceAppointment extends CreateRecord
{
    protected static string $resource = ServiceAppointmentResource::class;
}
