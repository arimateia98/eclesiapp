<?php

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Identity\Domain\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Person */
final class PersonResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'full_name' => $this->resource->full_name,
            'preferred_name' => $this->resource->preferred_name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'status' => $this->resource->status->value,
            'has_user' => $this->resource->user_id !== null,
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
