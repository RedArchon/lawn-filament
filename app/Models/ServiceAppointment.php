<?php

namespace App\Models;

use App\Contracts\BelongsToCompany as BelongsToCompanyContract;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAppointment extends Model implements BelongsToCompanyContract
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $with = ['company'];

    protected $fillable = [
        'company_id',
        'service_schedule_id',
        'property_id',
        'service_type_id',
        'team_id',
        'route_order',
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
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

    public function scopeAssignedToTeam(Builder $query, int $teamId): void
    {
        $query->where('team_id', $teamId);
    }

    public function scopeUnassigned(Builder $query): void
    {
        $query->whereNull('team_id');
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
