<?php

namespace App\Shared\Auditing;

final class AuditRecorder
{
    /**
     * @param  array<string, mixed>|null  $previousState
     * @param  array<string, mixed>  $newState
     */
    public function record(
        string $actorUserId,
        ?string $organizationId,
        AuditAction $action,
        string $entityType,
        string $entityId,
        ?array $previousState,
        array $newState,
        ?string $justification = null,
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_user_id' => $actorUserId,
            'organization_id' => $organizationId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'previous_state' => $previousState,
            'new_state' => $newState,
            'justification' => $justification,
            'created_at' => now(),
        ]);
    }
}
