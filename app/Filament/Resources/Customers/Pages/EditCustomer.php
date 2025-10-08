<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Property;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        if (($data['service_billing_address'] ?? false) &&
            $data['billing_address'] &&
            $data['billing_city'] &&
            $data['billing_state'] &&
            $data['billing_zip']) {

            // Check if a property with this exact address already exists for this customer
            $existingProperty = Property::where('customer_id', $this->record->id)
                ->where('address', $data['billing_address'])
                ->where('city', $data['billing_city'])
                ->where('state', $data['billing_state'])
                ->where('zip', $data['billing_zip'])
                ->first();

            if (! $existingProperty) {
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
}
