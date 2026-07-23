<?php

namespace App\Modules\Organizations\Domain\Models;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Organizations\Domain\Enums\OrganizationRelationshipStatus;
use App\Modules\Organizations\Domain\Enums\OrganizationRelationshipType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'source_organization_id',
    'target_organization_id',
    'relationship_type',
    'status',
    'started_at',
    'ended_at',
    'created_by',
])]
class OrganizationRelationship extends Model
{
    use HasUlids;

    /** @return BelongsTo<Organization, $this> */
    public function sourceOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'source_organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function targetOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'target_organization_id');
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
            'relationship_type' => OrganizationRelationshipType::class,
            'status' => OrganizationRelationshipStatus::class,
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }
}
