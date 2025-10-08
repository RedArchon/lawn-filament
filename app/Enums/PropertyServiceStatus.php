<?php

namespace App\Enums;

enum PropertyServiceStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Seasonal = 'seasonal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Seasonal => 'Seasonal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'danger',
            self::Seasonal => 'warning',
        };
    }

    public static function options(): array
    {
        return [
            self::Active->value => self::Active->getLabel(),
            self::Inactive->value => self::Inactive->getLabel(),
            self::Seasonal->value => self::Seasonal->getLabel(),
        ];
    }
}
