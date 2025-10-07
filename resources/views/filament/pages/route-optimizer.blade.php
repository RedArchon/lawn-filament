<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="optimize">
            {{ $this->form }}

            <div class="mt-6">
                <x-filament::button type="submit" size="lg">
                    Optimize Route
                </x-filament::button>
            </div>
        </form>

        @if ($optimizedRoute)
            <x-filament::section>
                <x-slot name="heading">
                    Optimized Route Results
                </x-slot>

                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-filament::card>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    {{ $optimizedRoute['total_distance_miles'] }} miles
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Total Distance
                                </div>
                            </div>
                        </x-filament::card>

                        <x-filament::card>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    {{ $optimizedRoute['total_duration_minutes'] }} minutes
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    Estimated Duration
                                </div>
                            </div>
                        </x-filament::card>
                    </div>

                    <div class="mt-6">
                        <h3 class="mb-4 text-lg font-semibold">Route Sequence</h3>
                        <div class="space-y-3">
                            @foreach ($optimizedRoute['properties'] as $index => $property)
                                <div class="flex items-center gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-600 text-lg font-bold text-white">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $property->customer->name }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $property->full_address }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
