<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'danger',
        };
    }

    public static function options(): array
    {
        return [
            self::Active->value => self::Active->getLabel(),
            self::Inactive->value => self::Inactive->getLabel(),
        ];
    }
}
