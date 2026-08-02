<?php

namespace App\Modules\Organizations\Domain\Models;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Enums\OrganizationStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationType;
use App\Modules\Organizations\Domain\Enums\OrganizationVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'type',
    'parent_organization_id',
    'status',
    'visibility',
    'timezone',
    'created_by',
])]
class Organization extends Model
{
    use HasUlids;

    /** @return BelongsTo<Organization, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_organization_id');
    }

    /** @return HasMany<Organization, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_organization_id');
    }

    /** @return HasMany<OrganizationMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    /** @return HasMany<OrganizationRelationship, $this> */
    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(OrganizationRelationship::class, 'source_organization_id');
    }

    /** @return HasMany<OrganizationRelationship, $this> */
    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(OrganizationRelationship::class, 'target_organization_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => OrganizationType::class,
            'status' => OrganizationStatus::class,
            'visibility' => OrganizationVisibility::class,
        ];
    }
}
