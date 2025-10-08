<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Property;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();

        if (($data['service_billing_address'] ?? false) &&
            $data['billing_address'] &&
            $data['billing_city'] &&
            $data['billing_state'] &&
            $data['billing_zip']) {

            Property::create([
                'customer_id' => $this->record->id,
                'address' => $data['billing_address'],
                'city' => $data['billing_city'],
                'state' => $data['billing_state'],
                'zip' => $data['billing_zip'],
                'service_status' => 'active',
            ]);
        }
    }
}
