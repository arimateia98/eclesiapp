<?php

namespace App\Modules\Ministries\Domain\Models;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'organization_id',
    'ministry_type_id',
    'name',
    'slug',
    'active',
    'created_by',
])]
class ServiceFunction extends Model
{
    use HasUlids;

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<MinistryType, $this> */
    public function ministryType(): BelongsTo
    {
        return $this->belongsTo(MinistryType::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<PersonFunction, $this> */
    public function personFunctions(): HasMany
    {
        return $this->hasMany(PersonFunction::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
