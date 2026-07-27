<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shared company relationship + a convenience scope for tenant isolation.
 * Data-isolation enforcement (auto-scoping queries to the current company)
 * is layered on in Phase 2 via a global scope; this keeps the relationship
 * consistent across all tenant-owned models today.
 */
trait BelongsToCompany
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeForCompany($query, int|Company $company)
    {
        $companyId = $company instanceof Company ? $company->id : $company;

        return $query->where($this->getTable().'.company_id', $companyId);
    }
}
