<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use App\Modules\EcclesialStructure\Models\Parish;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ParishUserMembership extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'joined_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Parish, $this> */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
