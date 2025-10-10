<x-filament-widgets::widget>
    <x-filament-actions::modals />
    
    @if ($isGeocoding)
        <div wire:poll.5s="checkGeocodingStatus"></div>
    @endif
    
    <x-filament::section>
        <x-slot name="heading">
            Property Location
        </x-slot>

        @if ($this->isGeocoded())
            <div class="space-y-4">
                <div class="rounded-lg overflow-hidden" style="height: 500px;" wire:ignore>
                    <div id="map" style="height: 100%; width: 100%;"></div>
                </div>

            </div>

        @else
            <div class="py-12 text-center space-y-4">
                <div class="flex justify-center">
                    <x-filament::icon
                        icon="heroicon-o-map"
                        class="w-16 h-16 text-gray-400 dark:text-gray-600"
                    />
                </div>

                <div class="space-y-2">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Property Not Geocoded
                    </h3>

                    <p class="text-sm text-gray-600 dark:text-gray-400 max-w-md mx-auto">
                        This property hasn't been geocoded yet. Geocode it to view its location on the map and enable route optimization.
                    </p>

                    @if ($this->geocodingFailed())
                        <div class="mt-4">
                            <x-filament::badge color="danger">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-o-exclamation-circle" class="w-4 h-4" />
                                    Geocoding Failed
                                </div>
                            </x-filament::badge>

                            @if ($this->getProperty()?->geocoding_error)
                                <p class="mt-2 text-xs text-red-600 dark:text-red-400">
                                    {{ $this->getProperty()->geocoding_error }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-6">
                    @if ($isGeocoding)
                        <div class="flex flex-col items-center gap-3">
                            <x-filament::loading-indicator class="h-8 w-8" />
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Geocoding in progress...
                            </p>
                        </div>
                    @else
                        <x-filament::button
                            wire:click="mountAction('geocode')"
                            icon="heroicon-o-map-pin"
                            color="primary"
                        >
                            Geocode Property
                        </x-filament::button>
                    @endif
                </div>

                <div class="mt-4 text-xs text-gray-500 dark:text-gray-500">
                    <p>Address: {{ $this->getPropertyAddress() }}</p>
                </div>
            </div>
        @endif
    </x-filament::section>

    @if ($this->isGeocoded())
        @script
        <script>
                   // Define the callback function in the global scope
                   window.initPropertyMap = function() {
                       @if ($this->getProperty()?->latitude && $this->getProperty()?->longitude)
                       const position = {
                           lat: {{ $this->getProperty()->latitude }},
                           lng: {{ $this->getProperty()->longitude }}
                       };
                       @else
                       // Fallback to Chicago if coordinates are invalid
                       const position = {
                           lat: 41.8781,
                           lng: -87.6298
                       };
                       @endif

                const map = new google.maps.Map(document.getElementById('map'), {
                    zoom: 17,
                    center: position,
                    mapTypeControl: true,
                    mapTypeControlOptions: {
                        style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                        position: google.maps.ControlPosition.TOP_RIGHT,
                        mapTypeIds: ['roadmap', 'satellite']
                    },
                    zoomControl: true,
                    zoomControlOptions: {
                        position: google.maps.ControlPosition.RIGHT_CENTER
                    },
                    streetViewControl: true,
                    streetViewControlOptions: {
                        position: google.maps.ControlPosition.RIGHT_CENTER
                    },
                    fullscreenControl: true,
                    fullscreenControlOptions: {
                        position: google.maps.ControlPosition.RIGHT_TOP
                    }
                });

                const marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: '{{ addslashes($this->getPropertyAddress()) }}',
                    animation: google.maps.Animation.DROP
                });

                const infoWindow = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 8px;">
                            <h3 style="font-weight: bold; margin-bottom: 4px;">{{ addslashes($this->getPropertyAddress()) }}</h3>
                            <p style="margin: 0; color: #666;">{{ $this->getProperty()?->customer?->name ?? 'Property' }}</p>
                        </div>
                    `
                });

                marker.addListener('click', () => {
                    infoWindow.open(map, marker);
                });
            };

            // Load Google Maps API
            if (!document.querySelector('script[src*="maps.googleapis.com"]')) {
                const script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key={{ config('services.google.api_key') }}&callback=initPropertyMap&loading=async';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            } else if (typeof google !== 'undefined' && google.maps) {
                // If already loaded, initialize immediately
                initPropertyMap();
            }
        </script>
        @endscript
    @endif
</x-filament-widgets::widget>

