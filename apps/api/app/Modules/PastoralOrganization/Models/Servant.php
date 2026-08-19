<?php

declare(strict_types=1);

namespace App\Modules\PastoralOrganization\Models;

use App\Modules\EcclesialStructure\Models\Parish;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CarbonImmutable|null $joined_on
 * @property CarbonImmutable|null $left_on
 */
final class Servant extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'joined_on' => 'immutable_date',
            'left_on' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Parish, $this> */
    public function parish(): BelongsTo
    {
        return $this->belongsTo(Parish::class);
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
