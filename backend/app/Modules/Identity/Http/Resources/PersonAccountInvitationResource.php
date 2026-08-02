<?php

namespace App\Modules\Identity\Http\Resources;

use App\Modules\Identity\Domain\Models\PersonAccountInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PersonAccountInvitation */
final class PersonAccountInvitationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'person_id' => $this->resource->person_id,
            'status' => $this->resource->status->value,
            'expires_at' => $this->resource->expiresAt()->toISOString(),
        ];
    }
}
