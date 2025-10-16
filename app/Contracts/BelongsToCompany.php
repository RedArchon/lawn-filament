<?php

namespace App\Contracts;

use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface BelongsToCompany
{
    public function company(): BelongsTo;

    public static function getCurrentCompany(): ?Company;
}

