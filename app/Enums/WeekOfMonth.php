<?php

namespace App\Enums;

enum WeekOfMonth: int
{
    case First = 1;
    case Second = 2;
    case Third = 3;
    case Fourth = 4;

    public function label(): string
    {
        return match ($this) {
            self::First => 'First Week',
            self::Second => 'Second Week',
            self::Third => 'Third Week',
            self::Fourth => 'Fourth Week',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
