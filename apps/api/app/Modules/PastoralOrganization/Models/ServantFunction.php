<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Models;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $qualified_on
 * @property CarbonImmutable|null $expires_on
 */
final class ServantFunction extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'qualified_on' => 'immutable_date',
            'expires_on' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Parish, $this> */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    /** @return BelongsTo<Servant, $this> */
    public function servant(): BelongsTo
    {
        return $this->belongsTo(Servant::class);
    }

    /** @return BelongsTo<PastoralFunction, $this> */
    public function pastoralFunction(): BelongsTo
    {
        return $this->belongsTo(PastoralFunction::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
