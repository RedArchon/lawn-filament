<?php

namespace App\Models;

use App\Enums\ServiceFrequency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonalFrequencyPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_schedule_id',
        'start_month',
        'start_day',
        'end_month',
        'end_day',
        'frequency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => ServiceFrequency::class,
            'start_month' => 'integer',
            'start_day' => 'integer',
            'end_month' => 'integer',
            'end_day' => 'integer',
        ];
    }

    public function serviceSchedule(): BelongsTo
    {
        return $this->belongsTo(ServiceSchedule::class);
    }

    public function containsDate(Carbon $date): bool
    {
        $month = $date->month;
        $day = $date->day;

        // Check if period crosses year boundary (e.g., Dec 1 - Jan 31)
        if ($this->start_month > $this->end_month) {
            // Period spans year boundary
            return $this->isDateInRange($month, $day, $this->start_month, $this->start_day, 12, 31)
                || $this->isDateInRange($month, $day, 1, 1, $this->end_month, $this->end_day);
        }

        // Normal period within same year
        return $this->isDateInRange($month, $day, $this->start_month, $this->start_day, $this->end_month, $this->end_day);
    }

    protected function isDateInRange(
        int $month,
        int $day,
        int $startMonth,
        int $startDay,
        int $endMonth,
        int $endDay
    ): bool {
        if ($month < $startMonth || $month > $endMonth) {
            return false;
        }

        if ($month === $startMonth && $day < $startDay) {
            return false;
        }

        if ($month === $endMonth && $day > $endDay) {
            return false;
        }

        return true;
    }

    public function getPeriodLabel(): string
    {
        $start = Carbon::create(null, $this->start_month, $this->start_day)->format('M j');
        $end = Carbon::create(null, $this->end_month, $this->end_day)->format('M j');

        return "{$start} - {$end}: {$this->frequency->getLabel()}";
    }
}
