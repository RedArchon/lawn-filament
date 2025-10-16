<?php

namespace App\Helpers;

use App\Models\Company;

class TenantHelper
{
    public static function currentCompany(): ?Company
    {
        $user = auth()->user();

        if (! $user || ! $user->company_id) {
            return null;
        }

        return $user->company;
    }

    public static function currentCompanyId(): ?int
    {
        return auth()->user()?->company_id;
    }

    public static function hasCompany(): bool
    {
        return auth()->check() && auth()->user()->company_id !== null;
    }
}

