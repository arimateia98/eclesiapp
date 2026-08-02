<?php

namespace App\Modules\Scheduling\Http\Resources;

use App\Modules\Scheduling\Domain\Models\Unavailability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Unavailability */
final class UnavailabilityResource extends JsonResource
{
    /** @return array<string, string> */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->resource->getKey(),
            'starts_at' => $this->resource->starts_at->toISOString(),
            'ends_at' => $this->resource->ends_at->toISOString(),
        ];
    }
}
