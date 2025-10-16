<?php

namespace App\Traits;

use App\Models\Company;
use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function ($model) {
            if (empty($model->company_id)) {
                $model->company_id = static::getCurrentCompany()?->id;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function getCurrentCompany(): ?Company
    {
        $user = auth()->user();

        if (! $user || ! $user->company_id) {
            return null;
        }

        return $user->company;
    }
}

