<?php

namespace App\Modules\Scheduling\Domain\Models;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Missions\Domain\Models\Mission;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Scheduling\Domain\Enums\EventStatus;
use App\Modules\Scheduling\Domain\Enums\EventVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'publisher_organization_id',
    'host_organization_id',
    'event_type_id',
    'location_id',
    'title',
    'description',
    'starts_at',
    'ends_at',
    'visibility',
    'status',
    'created_by',
])]
class Event extends Model
{
    use HasUlids;

    /** @return BelongsTo<Organization, $this> */
    public function publisherOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'publisher_organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function hostOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'host_organization_id');
    }

    /** @return BelongsTo<EventType, $this> */
    public function eventType(): BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    /** @return BelongsTo<Location, $this> */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Mission, $this> */
    public function missions(): HasMany
    {
        return $this->hasMany(Mission::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'visibility' => EventVisibility::class,
            'status' => EventStatus::class,
        ];
    }
}
