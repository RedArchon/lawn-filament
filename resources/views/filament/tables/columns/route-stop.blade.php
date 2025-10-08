<div class="flex items-center gap-3">
    <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $color === 'success' ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300' : ($color === 'primary' ? 'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300') }} text-xs font-bold">
        {{ $order }}
    </div>
    @if($isGeocoded)
        <x-filament::icon
            icon="heroicon-o-map-pin"
            class="h-4 w-4 text-success-500"
        />
    @else
        <x-filament::icon
            icon="heroicon-o-exclamation-triangle"
            class="h-4 w-4 text-warning-500"
        />
    @endif
</div>
