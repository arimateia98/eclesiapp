<?php

declare(strict_types=1);

namespace App\Modules\EcclesialStructure\Models;

use App\Modules\Identity\Models\ParishUserMembership;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Parish extends Model
{
    use HasUuids;

    protected $guarded = [];

    /** @return BelongsTo<Diocese, $this> */
    public function diocese(): BelongsTo
    {
        return $this->belongsTo(Diocese::class);
    }

    /** @return HasMany<Community, $this> */
    public function communities(): HasMany
    {
        return $this->hasMany(Community::class);
    }

    /** @return HasMany<Location, $this> */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /** @return HasMany<ParishUserMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(ParishUserMembership::class);
    }
}
