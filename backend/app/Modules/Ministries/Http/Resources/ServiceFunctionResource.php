<?php

namespace App\Modules\Ministries\Http\Resources;

use App\Modules\Ministries\Domain\Models\ServiceFunction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServiceFunction */
final class ServiceFunctionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'organization_id' => $this->resource->organization_id,
            'ministry_type_id' => $this->resource->ministry_type_id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'active' => $this->resource->active,
            'ministry_type' => new MinistryTypeResource($this->whenLoaded('ministryType')),
            'created_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
