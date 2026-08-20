<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Models;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PastoralFunction extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['requires_qualification' => 'boolean'];
    }

    /** @return BelongsTo<Parish, $this> */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    /** @return BelongsTo<PastoralArea, $this> */
    public function area(): BelongsTo
    {
        return $this->belongsTo(PastoralArea::class, 'pastoral_area_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<ServantFunction, $this> */
    public function servantFunctions(): HasMany
    {
        return $this->hasMany(ServantFunction::class);
    }
}
