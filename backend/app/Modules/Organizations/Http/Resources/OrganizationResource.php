<?php

namespace App\Modules\Organizations\Http\Resources;

use App\Modules\Organizations\Domain\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
final class OrganizationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'type' => $this->resource->type->value,
            'parent_organization_id' => $this->resource->parent_organization_id,
            'status' => $this->resource->status->value,
            'visibility' => $this->resource->visibility->value,
            'timezone' => $this->resource->timezone,
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
