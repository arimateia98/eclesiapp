<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Models;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PastoralArea extends Model
{
    use HasUuids;

    protected $guarded = [];

    /** @return BelongsTo<Parish, $this> */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<PastoralFunction, $this> */
    public function functions(): HasMany
    {
        return $this->hasMany(PastoralFunction::class);
    }
}
