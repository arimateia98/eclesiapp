<?php

namespace App\Modules\Scheduling\Http\Resources;

use App\Modules\Missions\Http\Resources\MissionResource;
use App\Modules\Scheduling\Domain\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Event */
final class EventResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'publisher_organization_id' => $this->resource->publisher_organization_id,
            'host_organization_id' => $this->resource->host_organization_id,
            'event_type_id' => $this->resource->event_type_id,
            'event_type' => new EventTypeResource($this->whenLoaded('eventType')),
            'location_id' => $this->resource->location_id,
            'location' => new LocationResource($this->whenLoaded('location')),
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'starts_at' => $this->resource->starts_at->toISOString(),
            'ends_at' => $this->resource->ends_at->toISOString(),
            'visibility' => $this->resource->visibility->value,
            'status' => $this->resource->status->value,
            'missions' => MissionResource::collection($this->whenLoaded('missions')),
            'created_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
