<?php

declare(strict_types=1);

namespace App\Modules\EcclesialStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Community extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_parish_seat' => 'boolean'];
    }

    /** @return BelongsTo<Parish, $this> */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    /** @return HasMany<Location, $this> */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
