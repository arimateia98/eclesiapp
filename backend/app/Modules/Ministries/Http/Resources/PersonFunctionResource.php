<?php

namespace App\Modules\Ministries\Http\Resources;

use App\Modules\Ministries\Domain\Models\PersonFunction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PersonFunction */
final class PersonFunctionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'organization_id' => $this->resource->organization_id,
            'person_id' => $this->resource->person_id,
            'service_function_id' => $this->resource->service_function_id,
            'service_function' => new ServiceFunctionResource($this->whenLoaded('serviceFunction')),
            'assigned_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
