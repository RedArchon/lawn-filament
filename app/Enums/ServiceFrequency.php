<?php

namespace App\Enums;

enum ServiceFrequency: string
{
    case Daily = 'daily';
    case Every5Days = 'every_5_days';
    case Every7Days = 'every_7_days';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Every3Weeks = 'every_3_weeks';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';

    public function getLabel(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::Every5Days => 'Every 5 Days',
            self::Every7Days => 'Every 7 Days',
            self::Weekly => 'Weekly',
            self::Biweekly => 'Biweekly (Every 2 Weeks)',
            self::Every3Weeks => 'Every 3 Weeks',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
        };
    }

    public function getDays(): int
    {
        return match ($this) {
            self::Daily => 1,
            self::Every5Days => 5,
            self::Every7Days => 7,
            self::Weekly => 7,
            self::Biweekly => 14,
            self::Every3Weeks => 21,
            self::Monthly => 30,
            self::Quarterly => 90,
        };
    }

    public static function options(): array
    {
        return [
            self::Daily->value => self::Daily->getLabel(),
            self::Every5Days->value => self::Every5Days->getLabel(),
            self::Every7Days->value => self::Every7Days->getLabel(),
            self::Weekly->value => self::Weekly->getLabel(),
            self::Biweekly->value => self::Biweekly->getLabel(),
            self::Every3Weeks->value => self::Every3Weeks->getLabel(),
            self::Monthly->value => self::Monthly->getLabel(),
            self::Quarterly->value => self::Quarterly->getLabel(),
        ];
    }
}
