<x-filament::page>
    <div class="space-y-6">
        {{-- Date Selection Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Select Date
            </x-slot>

            <form wire:submit="loadAppointments">
                <div wire:loading.delay class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                    <x-filament::loading-indicator class="inline-block size-4" />
                    Loading...
                </div>
                {{ $this->form }}
            </form>
        </x-filament::section>

        {{-- Optimized Route Results --}}
        @if($optimizedRoute)
            <x-filament::section>
                <x-slot name="heading">
                    Optimized Route
                </x-slot>

                <div class="space-y-4">
                    {{-- Route Summary --}}
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Distance</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $optimizedRoute['total_distance_miles'] }} miles
                            </div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Total Duration</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $optimizedRoute['total_duration_minutes'] }} minutes
                            </div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Stops</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">
                                {{ $optimizedRoute['appointment_count'] }}
                            </div>
                        </div>
                    </div>

                    {{-- Route Order --}}
                    <div class="mt-4">
                        <h4 class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Optimized Route Order:</h4>
                        <ol class="space-y-2">
                            @foreach($optimizedRoute['properties'] as $index => $property)
                                @php
                                    $appointment = $optimizedRoute['appointments']->firstWhere('property_id', $property->id);
                                @endphp
                                <li class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                    <div class="flex size-8 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-primary-900 dark:text-primary-300">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $property->full_address }}
                                        </div>
                                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                            {{ $property->customer->name }}
                                            @if($appointment)
                                                • {{ $appointment->serviceType->name }}
                                                @if($appointment->scheduled_time)
                                                    • {{ \Carbon\Carbon::parse($appointment->scheduled_time)->format('g:i A') }}
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament::page>

