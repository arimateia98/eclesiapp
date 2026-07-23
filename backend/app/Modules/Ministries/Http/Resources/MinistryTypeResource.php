<?php

namespace App\Modules\Ministries\Http\Resources;

use App\Modules\Ministries\Domain\Models\MinistryType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MinistryType */
final class MinistryTypeResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'organization_id' => $this->resource->organization_id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'description' => $this->resource->description,
            'active' => $this->resource->active,
            'created_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
