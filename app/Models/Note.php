<?php

namespace App\Models;

use App\Contracts\BelongsToCompany as BelongsToCompanyContract;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model implements BelongsToCompanyContract
{
    use BelongsToCompany, HasFactory;

    protected $with = ['company'];

    protected $fillable = [
        'company_id',
        'content',
        'notable_type',
        'notable_id',
        'created_by',
    ];

    public function notable(): MorphTo
    {
        return $this->morphTo();
    }
}
