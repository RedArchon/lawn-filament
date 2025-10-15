<?php

namespace App\Models;

use App\Contracts\BelongsToCompany as BelongsToCompanyContract;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model implements BelongsToCompanyContract
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $with = ['company'];

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'company_name',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function serviceSchedules(): HasManyThrough
    {
        return $this->hasManyThrough(ServiceSchedule::class, Property::class);
    }
}
