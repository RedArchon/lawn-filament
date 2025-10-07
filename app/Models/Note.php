<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
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
