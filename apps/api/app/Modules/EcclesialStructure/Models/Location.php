<?php

declare(strict_types=1);

namespace App\Modules\EcclesialStructure\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Location extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['address_json' => 'array'];
    }

    /** @return BelongsTo<Parish, $this> */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    /** @return BelongsTo<Community, $this> */
    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }
}
