<?php

namespace App\Modules\Missions\Http\Resources;

use App\Modules\Ministries\Http\Resources\ServiceFunctionResource;
use App\Modules\Missions\Domain\Models\MissionSlot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MissionSlot */
final class MissionSlotResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'mission_id' => $this->resource->mission_id,
            'slot_type' => $this->resource->slot_type->value,
            'service_function_id' => $this->resource->service_function_id,
            'service_function' => new ServiceFunctionResource($this->whenLoaded('serviceFunction')),
            'quantity' => $this->resource->quantity,
            'required' => $this->resource->required,
            'created_at' => $this->resource->created_at->toISOString(),
        ];
    }
}
