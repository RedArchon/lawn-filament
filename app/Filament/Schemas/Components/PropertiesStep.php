<?php

namespace App\Filament\Schemas\Components;

use Filament\Schemas\Components\Component;

class PropertiesStep extends Component
{
    protected string $view = 'filament.schemas.components.properties-step';

    public static function make(): static
    {
        return app(static::class);
    }
}
