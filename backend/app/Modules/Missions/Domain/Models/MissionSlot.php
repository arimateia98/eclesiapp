<?php

namespace App\Modules\Missions\Domain\Models;

use App\Modules\Ministries\Domain\Models\ServiceFunction;
use App\Modules\Missions\Domain\Enums\MissionSlotType;
use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_id',
    'mission_id',
    'slot_type',
    'service_function_id',
    'quantity',
    'required',
])]
class MissionSlot extends Model
{
    use HasUlids;

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Mission, $this> */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /** @return BelongsTo<ServiceFunction, $this> */
    public function serviceFunction(): BelongsTo
    {
        return $this->belongsTo(ServiceFunction::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'slot_type' => MissionSlotType::class,
            'quantity' => 'integer',
            'required' => 'boolean',
        ];
    }
}
