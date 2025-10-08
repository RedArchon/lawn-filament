<?php

namespace App\Filament\Resources\ServiceSchedules\Pages;

use App\Filament\Resources\ServiceSchedules\ServiceScheduleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceSchedules extends ListRecords
{
    protected static string $resource = ServiceScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
