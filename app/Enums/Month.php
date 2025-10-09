<?php

namespace App\Enums;

enum Month: int
{
    case January = 1;
    case February = 2;
    case March = 3;
    case April = 4;
    case May = 5;
    case June = 6;
    case July = 7;
    case August = 8;
    case September = 9;
    case October = 10;
    case November = 11;
    case December = 12;

    public function label(): string
    {
        return $this->name;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }

    public function shortName(): string
    {
        return match ($this) {
            self::January => 'Jan',
            self::February => 'Feb',
            self::March => 'Mar',
            self::April => 'Apr',
            self::May => 'May',
            self::June => 'Jun',
            self::July => 'Jul',
            self::August => 'Aug',
            self::September => 'Sep',
            self::October => 'Oct',
            self::November => 'Nov',
            self::December => 'Dec',
        };
    }
}
