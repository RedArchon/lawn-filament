<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAppointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_schedule_id',
        'property_id',
        'service_type_id',
        'scheduled_date',
        'scheduled_time',
        'status',
        'completed_at',
        'completed_by',
        'duration_minutes',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'scheduled_date' => 'date',
            'scheduled_time' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function serviceSchedule(): BelongsTo
    {
        return $this->belongsTo(ServiceSchedule::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function scopeScheduled(Builder $query): void
    {
        $query->where('status', 'scheduled');
    }

    public function scopeForDate(Builder $query, Carbon $date): void
    {
        $query->whereDate('scheduled_date', $date);
    }

    public function scopeReadyForRouting(Builder $query): void
    {
        $query->where('status', 'scheduled')
            ->whereHas('property', function (Builder $q) {
                $q->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->where('geocoding_failed', false);
            });
    }

    public function markCompleted(User $user): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);
    }

    public function markCancelled(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function markSkipped(): void
    {
        $this->update([
            'status' => 'skipped',
        ]);
    }
}
