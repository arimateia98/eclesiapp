<?php

namespace App\Modules\Scheduling\Http\Resources;

use App\Modules\Scheduling\Domain\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EventType */
final class EventTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'organization_id' => $this->resource->organization_id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'active' => $this->resource->active,
            'created_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
