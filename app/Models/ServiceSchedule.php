<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class ServiceSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'property_id',
        'service_type_id',
        'frequency',
        'start_date',
        'end_date',
        'day_of_week',
        'week_of_month',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => 'string',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(ServiceAppointment::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeDueForGeneration(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(function (Builder $q) {
                $q->where('start_date', '<=', now())
                    ->where(function (Builder $sq) {
                        $sq->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    });
            });
    }

    public function generateAppointments(Carbon $startDate, Carbon $endDate): Collection
    {
        $appointments = collect();
        $current = $this->start_date->copy();

        if ($current->lt($startDate)) {
            $current = $startDate->copy();
        }

        while ($current->lte($endDate)) {
            if ($this->end_date && $current->gt($this->end_date)) {
                break;
            }

            $appointmentDate = $this->calculateNextAppointmentDate($current);

            if ($appointmentDate && $appointmentDate->lte($endDate)) {
                if (! $this->end_date || $appointmentDate->lte($this->end_date)) {
                    $existing = ServiceAppointment::query()
                        ->where('property_id', $this->property_id)
                        ->where('service_type_id', $this->service_type_id)
                        ->whereDate('scheduled_date', $appointmentDate)
                        ->exists();

                    if (! $existing) {
                        $appointment = ServiceAppointment::create([
                            'service_schedule_id' => $this->id,
                            'property_id' => $this->property_id,
                            'service_type_id' => $this->service_type_id,
                            'scheduled_date' => $appointmentDate,
                            'status' => 'scheduled',
                        ]);

                        $appointments->push($appointment);
                    }
                }
            }

            $current = $this->advanceCurrentDate($current);
        }

        return $appointments;
    }

    protected function calculateNextAppointmentDate(Carbon $date): ?Carbon
    {
        return match ($this->frequency) {
            'weekly' => $this->calculateWeeklyDate($date),
            'biweekly' => $this->calculateBiweeklyDate($date),
            'monthly' => $this->calculateMonthlyDate($date),
            'quarterly' => $this->calculateQuarterlyDate($date),
            default => null,
        };
    }

    protected function calculateWeeklyDate(Carbon $date): Carbon
    {
        $targetDay = $this->day_of_week ?? $this->start_date->dayOfWeek;

        return $date->copy()->next($targetDay === 0 ? 'Sunday' : Carbon::getDays()[$targetDay]);
    }

    protected function calculateBiweeklyDate(Carbon $date): Carbon
    {
        $targetDay = $this->day_of_week ?? $this->start_date->dayOfWeek;
        $nextDate = $date->copy()->next($targetDay === 0 ? 'Sunday' : Carbon::getDays()[$targetDay]);

        $weeksSinceStart = $this->start_date->diffInWeeks($nextDate);

        if ($weeksSinceStart % 2 !== 0) {
            $nextDate->addWeek();
        }

        return $nextDate;
    }

    protected function calculateMonthlyDate(Carbon $date): Carbon
    {
        $targetDay = $this->day_of_week ?? $this->start_date->dayOfWeek;
        $weekOfMonth = $this->week_of_month ?? 1;

        return $date->copy()->addMonth()->nthOfMonth($weekOfMonth, $targetDay);
    }

    protected function calculateQuarterlyDate(Carbon $date): Carbon
    {
        return $date->copy()->addMonths(3);
    }

    protected function advanceCurrentDate(Carbon $date): Carbon
    {
        return match ($this->frequency) {
            'weekly' => $date->copy()->addWeek(),
            'biweekly' => $date->copy()->addWeeks(2),
            'monthly' => $date->copy()->addMonth(),
            'quarterly' => $date->copy()->addMonths(3),
            default => $date->copy()->addDay(),
        };
    }
}
