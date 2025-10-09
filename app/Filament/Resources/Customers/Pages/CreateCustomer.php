<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Enums\State;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Customer;
use App\Models\Property;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                    TextInput::make('name')
                        ->required()
                        ->minLength(2)
                        ->maxLength(255)
                        ->placeholder('John Doe')
                        ->helperText('Full name of the customer'),

                    TextInput::make('email')
                        ->required()
                        ->email()
                        ->unique('customers', 'email', ignoreRecord: true)
                        ->maxLength(255)
                        ->placeholder('customer@example.com')
                        ->helperText('Primary email address for communication'),

                    TextInput::make('phone')
                        ->required()
                        ->tel()
                        ->regex('/^[\d\s\-\(\)\+]+$/')
                        ->maxLength(255)
                        ->placeholder('(555) 123-4567')
                        ->helperText('Primary contact phone number'),
                ])
                ->columns(3),

            Step::make('Company Details')
                ->description('Optional business information and status')
                ->icon(Heroicon::BuildingOffice)
                ->schema([
                    TextInput::make('company_name')
                        ->minLength(2)
                        ->maxLength(255)
                        ->nullable()
                        ->placeholder('Company Name LLC')
                        ->helperText('Optional: If customer is a business')
                        ->columnSpan(2),

                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ])
                        ->default('active')
                        ->required()
                        ->helperText('Customer account status'),
                ])
                ->columns(2),

            Step::make('Billing Address')
                ->description('Customer billing address and property setup')
                ->icon(Heroicon::MapPin)
                ->schema([
                    TextInput::make('billing_address')
                        ->minLength(5)
                        ->maxLength(255)
                        ->nullable()
                        ->requiredIf('service_billing_address', true)
                        ->placeholder('123 Main Street')
                        ->columnSpanFull(),

                    TextInput::make('billing_city')
                        ->minLength(2)
                        ->maxLength(255)
                        ->nullable()
                        ->requiredIf('service_billing_address', true)
                        ->placeholder('City'),

                    Select::make('billing_state')
                        ->options(State::options())
                        ->searchable()
                        ->nullable()
                        ->requiredIf('service_billing_address', true)
                        ->placeholder('Select state'),

                    TextInput::make('billing_zip')
                        ->regex('/^\d{5}(-\d{4})?$/')
                        ->maxLength(255)
                        ->nullable()
                        ->requiredIf('service_billing_address', true)
                        ->placeholder('12345')
                        ->mask('99999'),

                    Checkbox::make('service_billing_address')
                        ->label('Use this address for property service')
                        ->helperText('Check this to create a property record at this address')
                        ->visible(true)
                        ->columnSpanFull()
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if ($state && $get('billing_address') && $get('billing_city') && $get('billing_state') && $get('billing_zip')) {
                                $this->prePopulatePropertiesRepeater($get, $set);
                            }
                        }),
                ])
                ->columns(3)
                ->afterValidation(function () {
                    $this->saveCustomerForPropertiesStep();
                }),

            Step::make('Properties')
                ->description('Manage customer properties and locations')
                ->icon(Heroicon::Home)
                ->schema([
                    CustomerForm::getPropertiesRepeaterField(),
                ]),
        ];
    }

    protected function afterCreate(): void
    {
        $this->createPropertiesFromFormData();
    }

    public function create(bool $another = false): void
    {
        if ($this->customerAlreadyExists()) {
            $this->afterCreate();

            return;
        }

        parent::create($another);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }

    private function saveCustomerForPropertiesStep(): void
    {
        if (! $this->record) {
            $data = $this->form->getState();
            $this->record = Customer::create($data);
        }
    }

    private function customerAlreadyExists(): bool
    {
        return $this->record !== null;
    }

    private function createPropertiesFromFormData(): void
    {
        $data = $this->form->getRawState();
        $properties = $data['properties'] ?? [];

        if (! is_array($properties)) {
            return;
        }

        foreach ($properties as $propertyData) {
            if ($this->hasValidPropertyData($propertyData)) {
                $this->createProperty($propertyData);
            }
        }
    }

    private function hasValidPropertyData(array $propertyData): bool
    {
        return ! empty($propertyData['address']);
    }

    private function createProperty(array $propertyData): void
    {
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

    private function prePopulatePropertiesRepeater(callable $get, callable $set): void
    {
        $billingAddress = $get('billing_address');
        $billingCity = $get('billing_city');
        $billingState = $get('billing_state');
        $billingZip = $get('billing_zip');

        // Check if this billing address is already in the properties
        $existingProperties = $get('properties') ?? [];
        foreach ($existingProperties as $property) {
            if (
                ($property['address'] ?? null) === $billingAddress &&
                ($property['city'] ?? null) === $billingCity &&
                ($property['state'] ?? null) === $billingState &&
                ($property['zip'] ?? null) === $billingZip
            ) {
                return; // Already exists, don't add duplicate
            }
        }

        // Add the billing address as a new property
        $newProperty = [
            'address' => $billingAddress,
            'city' => $billingCity,
            'state' => $billingState,
            'zip' => $billingZip,
            'service_status' => 'active',
            'lot_size' => null,
            'access_instructions' => null,
        ];

        $updatedProperties = array_merge($existingProperties, [$newProperty]);
        $set('properties', $updatedProperties);
    }
}
