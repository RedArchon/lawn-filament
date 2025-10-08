<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'address',
        'city',
        'state',
        'zip',
        'latitude',
        'longitude',
        'geocoded_at',
        'geocoding_failed',
        'geocoding_error',
        'lot_size',
        'access_instructions',
        'service_status',
    ];

    protected function casts(): array
    {
        return [
            'service_status' => 'string',
            'geocoding_failed' => 'boolean',
            'geocoded_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function serviceSchedules(): HasMany
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    public function serviceAppointments(): HasMany
    {
        return $this->hasMany(ServiceAppointment::class);
    }

    public function fullAddress(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->address}, {$this->city}, {$this->state} {$this->zip}")
        );
    }

    public function scopeGeocoded(Builder $query): void
    {
        $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('geocoding_failed', false);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('service_status', 'active');
    }

    public function scopeNeedsGeocoding(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereNull('latitude')
                ->orWhereNull('longitude')
                ->orWhere('geocoding_failed', true);
        });
    }
}
