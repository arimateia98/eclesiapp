<?php

namespace App\Modules\Scheduling\Http\Resources;

use App\Modules\Scheduling\Domain\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Location */
final class LocationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'organization_id' => $this->resource->organization_id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'address_line' => $this->resource->address_line,
            'city' => $this->resource->city,
            'timezone' => $this->resource->timezone,
            'active' => $this->resource->active,
            'created_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
