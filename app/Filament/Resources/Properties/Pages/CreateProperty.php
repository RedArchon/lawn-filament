<?php

namespace App\Filament\Resources\Properties\Pages;

use App\Filament\Resources\Properties\PropertyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Check if customer parameter exists in the URL (from nested resource)
        $customerId = request()->query('customer');

        if ($customerId) {
            $data['customer_id'] = $customerId;
        }

        return $data;
    }
}
