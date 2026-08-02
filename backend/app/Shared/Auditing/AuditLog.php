<?php

namespace App\Shared\Auditing;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'actor_user_id',
    'organization_id',
    'action',
    'entity_type',
    'entity_id',
    'previous_state',
    'new_state',
    'justification',
    'created_at',
])]
final class AuditLog extends Model
{
    use HasUlids;

    public const UPDATED_AT = null;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'previous_state' => 'array',
            'new_state' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
