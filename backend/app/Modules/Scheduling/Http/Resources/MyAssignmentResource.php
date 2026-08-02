<?php

namespace App\Modules\Scheduling\Http\Resources;

use App\Modules\Scheduling\Domain\Models\Assignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Assignment */
final class MyAssignmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $event = $this->resource->mission->event;

        return [
            'id' => (string) $this->resource->getKey(),
            'status' => $this->resource->status->value,
            'organization' => [
                'id' => (string) $this->resource->organization->getKey(),
                'name' => $this->resource->organization->name,
            ],
            'event' => [
                'id' => (string) $event->getKey(),
                'title' => $event->title,
                'starts_at' => $event->starts_at->toISOString(),
                'ends_at' => $event->ends_at->toISOString(),
                'location' => $event->location?->name,
            ],
            'mission' => [
                'id' => (string) $this->resource->mission->getKey(),
                'title' => $this->resource->mission->title,
            ],
            'function' => [
                'id' => (string) $this->resource->missionSlot->serviceFunction?->getKey(),
                'name' => $this->resource->missionSlot->serviceFunction?->name,
            ],
        ];
    }
}
