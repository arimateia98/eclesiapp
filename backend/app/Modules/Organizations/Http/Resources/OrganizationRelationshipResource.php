<?php

namespace App\Modules\Organizations\Http\Resources;

use App\Modules\Organizations\Domain\Models\OrganizationRelationship;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrganizationRelationship */
final class OrganizationRelationshipResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'source_organization_id' => $this->resource->source_organization_id,
            'target_organization_id' => $this->resource->target_organization_id,
            'relationship_type' => $this->resource->relationship_type->value,
            'status' => $this->resource->status->value,
            'started_at' => $this->resource->started_at->toISOString(),
            'ended_at' => $this->resource->ended_at?->toISOString(),
        ];
    }
}
