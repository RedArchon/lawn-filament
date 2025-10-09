<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Property;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\View;
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
                        $this->save();
                    }
                }),

            Step::make('Properties')
                ->description('Manage customer properties and locations')
                ->icon(Heroicon::Home)
                ->schema([
                    View::make('wizard.properties-step')
                        ->viewData([
                            'customer' => $this->record ?? null,
                        ]),
                ]),
        ];
    }

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If record already exists (created in billing step), don't create again
        if ($this->record) {
            return [];
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
