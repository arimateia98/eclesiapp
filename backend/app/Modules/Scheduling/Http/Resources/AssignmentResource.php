<?php

namespace App\Modules\Scheduling\Http\Resources;

use App\Modules\Identity\Http\Resources\PersonResource;
use App\Modules\Missions\Http\Resources\MissionSlotResource;
use App\Modules\Scheduling\Domain\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Assignment */
final class AssignmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(), 'mission_id' => $this->resource->mission_id,
            'mission_slot_id' => $this->resource->mission_slot_id, 'person_id' => $this->resource->person_id,
            'person' => new PersonResource($this->whenLoaded('person')),
            'mission_slot' => new MissionSlotResource($this->whenLoaded('missionSlot')),
            'status' => $this->resource->status->value, 'assigned_at' => $this->resource->assigned_at->toISOString(),
        ];
    }
}
