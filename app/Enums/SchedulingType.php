<?php

namespace App\Enums;

enum SchedulingType: string
{
    case Manual = 'manual';
    case Recurring = 'recurring';
    case Seasonal = 'seasonal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Manual => 'Manual (One-Off)',
            self::Recurring => 'Recurring',
            self::Seasonal => 'Seasonal',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Manual => 'Single service appointment',
            self::Recurring => 'Simple repeating schedule with fixed frequency',
            self::Seasonal => 'Variable frequency based on seasons/months',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Manual => 'gray',
            self::Recurring => 'info',
            self::Seasonal => 'success',
        };
    }

    public static function options(): array
    {
        return [
            self::Manual->value => self::Manual->getLabel(),
            self::Recurring->value => self::Recurring->getLabel(),
            self::Seasonal->value => self::Seasonal->getLabel(),
        ];
    }
}
