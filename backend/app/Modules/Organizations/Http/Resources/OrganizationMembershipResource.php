<?php

namespace App\Modules\Organizations\Http\Resources;

use App\Modules\Identity\Http\Resources\PersonResource;
use App\Modules\Organizations\Domain\Models\OrganizationMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrganizationMembership */
final class OrganizationMembershipResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'organization_id' => $this->resource->organization_id,
            'role' => $this->resource->role->value,
            'status' => $this->resource->status->value,
            'joined_at' => $this->resource->joined_at->toISOString(),
            'person' => new PersonResource($this->whenLoaded('person')),
        ];
    }
}
