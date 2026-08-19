<?php

declare(strict_types=1);

namespace App\Modules\EcclesialStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Diocese extends Model
{
    use HasUuids;

    protected $guarded = [];

    /** @return HasMany<Parish, $this> */
    public function parishes(): HasMany
    {
        return $this->hasMany(Parish::class);
    }
}
