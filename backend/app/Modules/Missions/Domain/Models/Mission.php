<?php

namespace App\Modules\Missions\Domain\Models;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Ministries\Domain\Models\MinistryType;
use App\Modules\Missions\Domain\Enums\MissionParticipationPolicy;
use App\Modules\Missions\Domain\Enums\MissionStatus;
use App\Modules\Missions\Domain\Enums\MissionVisibility;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Domain\Models\Event;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'event_id',
    'publisher_organization_id',
    'target_organization_id',
    'ministry_type_id',
    'title',
    'description',
    'visibility',
    'participation_policy',
    'status',
    'response_deadline',
    'created_by',
])]
class Mission extends Model
{
    use HasUlids;

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function publisherOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'publisher_organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function targetOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'target_organization_id');
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

    /** @return HasMany<MissionSlot, $this> */
    public function slots(): HasMany
    {
        return $this->hasMany(MissionSlot::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'visibility' => MissionVisibility::class,
            'participation_policy' => MissionParticipationPolicy::class,
            'status' => MissionStatus::class,
            'response_deadline' => 'immutable_datetime',
        ];
    }
}
