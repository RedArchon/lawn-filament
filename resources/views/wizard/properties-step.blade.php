<div>
    @if($customer)
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Customer Properties
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Manage properties for {{ $customer->name }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    @if($customer->properties->count() > 0)
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <h4 class="text-md font-medium text-gray-900 dark:text-white">
                                    Existing Properties ({{ $customer->properties->count() }})
                                </h4>
                                <a href="{{ route('filament.admin.resources.customers.edit', ['record' => $customer->id, 'relation' => 0]) }}" 
                                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Property
                                </a>
                            </div>

                            <div class="grid gap-4">
                                @foreach($customer->properties as $property)
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h5 class="font-medium text-gray-900 dark:text-white">
                                                    {{ $property->address }}
                                                </h5>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $property->city }}, {{ $property->state }} {{ $property->zip }}
                                                </p>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-2
                                                    {{ $property->service_status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200' }}">
                                                    {{ ucfirst($property->service_status) }}
                                                </span>
                                            </div>
                                            <div class="flex space-x-2">
                                                <a href="{{ route('filament.admin.resources.properties.edit', $property->id) }}" 
                                                   class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No properties yet</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Get started by adding the first property for this customer.
                            </p>
                            <div class="mt-6">
                                <a href="{{ route('filament.admin.resources.properties.create', ['customer_id' => $customer->id]) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add First Property
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            Properties Management
                        </h3>
                        <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            <p>
                                You can add, edit, and manage properties for this customer. 
                                Properties created here will be available for service scheduling.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <div class="animate-pulse">
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mx-auto mb-4"></div>
                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/2 mx-auto"></div>
            </div>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Customer information will be available after completing the previous steps.
            </p>
        </div>
    @endif
</div>
