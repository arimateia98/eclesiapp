<?php

namespace App\Modules\Missions\Http\Resources;

use App\Modules\Ministries\Http\Resources\MinistryTypeResource;
use App\Modules\Missions\Domain\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Mission */
final class MissionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'event_id' => $this->resource->event_id,
            'publisher_organization_id' => $this->resource->publisher_organization_id,
            'target_organization_id' => $this->resource->target_organization_id,
            'ministry_type_id' => $this->resource->ministry_type_id,
            'ministry_type' => new MinistryTypeResource($this->whenLoaded('ministryType')),
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'visibility' => $this->resource->visibility->value,
            'participation_policy' => $this->resource->participation_policy->value,
            'status' => $this->resource->status->value,
            'response_deadline' => $this->resource->response_deadline?->toISOString(),
            'slots' => MissionSlotResource::collection($this->whenLoaded('slots')),
            'created_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
