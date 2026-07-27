<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Auto-populates a public-facing ULID on create and exposes it as the route
 * key so internal auto-increment ids are never leaked in URLs or APIs.
 */
trait HasUlid
{
    public static function bootHasUlid(): void
    {
        static::creating(function ($model) {
            if (empty($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
