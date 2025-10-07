<x-filament::page>
    <div class="space-y-6">
        {{-- Date Selection Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Select Date
            </x-slot>

            <form wire:submit="loadAppointments">
                {{ $this->form }}
            </form>
        </x-filament::section>

        @php
            $stats = $this->getAppointmentStats();
        @endphp

        {{-- Summary Stats --}}
        @if($appointments && $appointments->isNotEmpty())
            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                            {{ $stats['total'] }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Total Appointments
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-success-600 dark:text-success-400">
                            {{ $stats['geocoded'] }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Ready for Routing
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold {{ $stats['not_geocoded'] > 0 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-600 dark:text-gray-400' }}">
                            {{ $stats['not_geocoded'] }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Need Geocoding
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $stats['total_duration'] }}
                        </div>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Total Minutes
                        </div>
                    </div>
                </x-filament::section>
            </div>
        @endif

        {{-- Appointments List --}}
        @if($appointments && $appointments->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    Scheduled Appointments for {{ \Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}
                </x-slot>

                <div class="space-y-3">
                    @foreach($appointments as $appointment)
                        <div class="flex items-center justify-between rounded-lg border border-gray-300 p-4 dark:border-gray-600">
                            <div class="flex items-start gap-4">
                                {{-- Geocoding Status Indicator --}}
                                <div class="flex-shrink-0">
                                    @if($appointment->property->latitude && $appointment->property->longitude && !$appointment->property->geocoding_failed)
                                        <div class="flex size-10 items-center justify-center rounded-full bg-success-100 dark:bg-success-900">
                                            <x-filament::icon
                                                icon="heroicon-o-check-circle"
                                                class="size-5 text-success-600 dark:text-success-400"
                                            />
                                        </div>
                                    @else
                                        <div class="flex size-10 items-center justify-center rounded-full bg-warning-100 dark:bg-warning-900">
                                            <x-filament::icon
                                                icon="heroicon-o-exclamation-triangle"
                                                class="size-5 text-warning-600 dark:text-warning-400"
                                            />
                                        </div>
                                    @endif
                                </div>

                                {{-- Appointment Details --}}
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $appointment->property->full_address }}
                                        </h3>
                                        <x-filament::badge :color="match($appointment->status) {
                                            'scheduled' => 'gray',
                                            'in_progress' => 'warning',
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'skipped' => 'gray',
                                            default => 'gray'
                                        }">
                                            {{ ucfirst($appointment->status) }}
                                        </x-filament::badge>
                                    </div>
                                    <div class="mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                        <div>
                                            <span class="font-medium">Customer:</span> {{ $appointment->property->customer->name }}
                                        </div>
                                        <div>
                                            <span class="font-medium">Service:</span> {{ $appointment->serviceType->name }}
                                        </div>
                                        @if($appointment->scheduled_time)
                                            <div>
                                                <span class="font-medium">Time:</span> {{ \Carbon\Carbon::parse($appointment->scheduled_time)->format('g:i A') }}
                                            </div>
                                        @endif
                                        @if($appointment->duration_minutes)
                                            <div>
                                                <span class="font-medium">Duration:</span> {{ $appointment->duration_minutes }} minutes
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @elseif($selectedDate)
            <x-filament::section>
                <div class="py-12 text-center">
                    <x-filament::icon
                        icon="heroicon-o-calendar"
                        class="mx-auto size-12 text-gray-400"
                    />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">
                        No appointments scheduled
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        There are no service appointments for {{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}.
                    </p>
                </div>
            </x-filament::section>
        @endif

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

