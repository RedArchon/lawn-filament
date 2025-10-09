<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Customer;
use App\Models\Property;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Support\Icons\Heroicon;

class CreateCustomer extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = CustomerResource::class;

    protected function getSteps(): array
    {
        return [
            Step::make('Contact Information')
                ->description('Basic customer contact details')
                ->icon(Heroicon::User)
                ->schema([
                    CustomerForm::getNameFormField(),
                    CustomerForm::getEmailFormField(),
                    CustomerForm::getPhoneFormField(),
                ])
                ->columns(3),

            Step::make('Company Details')
                ->description('Optional business information and status')
                ->icon(Heroicon::BuildingOffice)
                ->schema([
                    CustomerForm::getCompanyNameFormField(),
                    CustomerForm::getStatusFormField(),
                ])
                ->columns(2),

            Step::make('Billing Address')
                ->description('Customer billing address and property setup')
                ->icon(Heroicon::MapPin)
                ->schema([
                    CustomerForm::getBillingAddressFormField(),
                    CustomerForm::getBillingCityFormField(),
                    CustomerForm::getBillingStateFormField(),
                    CustomerForm::getBillingZipFormField(),
                    CustomerForm::getServiceBillingAddressFormField(),
                ])
                ->columns(3)
                ->afterValidation(function () {
                    // Save the customer after billing address step so it's available for properties step
                    if (! $this->record) {
                        $data = $this->form->getState();
                        $this->record = Customer::create($data);
                    }
                }),

            Step::make('Properties')
                ->description('Manage customer properties and locations')
                ->icon(Heroicon::Home)
                ->schema([
                    CustomerForm::getPropertiesRepeaterField(),
                ])
                ->beforeValidation(function () {
                    // Pre-populate properties repeater with billing address if service_billing_address is checked
                    $data = $this->form->getState();

                    if (($data['service_billing_address'] ?? false) &&
                        $data['billing_address'] &&
                        $data['billing_city'] &&
                        $data['billing_state'] &&
                        $data['billing_zip']) {

                        // Check if properties repeater is empty
                        $properties = $data['properties'] ?? [];

                        if (empty($properties)) {
                            // Pre-populate with billing address
                            $this->form->set('properties', [[
                                'address' => $data['billing_address'],
                                'city' => $data['billing_city'],
                                'state' => $data['billing_state'],
                                'zip' => $data['billing_zip'],
                                'service_status' => 'active',
                                'lot_size' => null,
                                'access_instructions' => null,
                            ]]);
                        }
                    }
                }),
        ];
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();

        // Create properties from the repeater
        if (isset($data['properties']) && is_array($data['properties'])) {
            foreach ($data['properties'] as $propertyData) {
                if (! empty($propertyData['address'])) {
                    Property::create([
                        'customer_id' => $this->record->id,
                        'address' => $propertyData['address'],
                        'city' => $propertyData['city'],
                        'state' => $propertyData['state'],
                        'zip' => $propertyData['zip'],
                        'service_status' => $propertyData['service_status'] ?? 'active',
                        'lot_size' => $propertyData['lot_size'] ?? null,
                        'access_instructions' => $propertyData['access_instructions'] ?? null,
                    ]);
                }
            }
        }
    }

    public function create(bool $another = false): void
    {
        // If record already exists (created in billing step), skip creation
        if ($this->record) {
            // Just call afterCreate to handle properties
            $this->afterCreate();

            return;
        }

        parent::create($another);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
