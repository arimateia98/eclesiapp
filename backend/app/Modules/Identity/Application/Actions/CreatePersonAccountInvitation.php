<?php

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Domain\Enums\AccountInvitationStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\PersonAccountInvitation;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Infrastructure\Mail\PersonAccountInvitationMail;
use App\Modules\Organizations\Domain\Enums\MembershipStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use App\Modules\Organizations\Domain\Services\OrganizationAccess;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final readonly class CreatePersonAccountInvitation
{
    public function __construct(
        private OrganizationAccess $access,
        private AuditRecorder $audit,
    ) {}

    public function execute(
        User $actor,
        Organization $organization,
        string $personId,
    ): PersonAccountInvitation {
        if (! $this->access->canManageMembers($actor, $organization)) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $organization, $personId): PersonAccountInvitation {
            $person = Person::query()
                ->whereKey($personId)
                ->whereHas('memberships', fn ($query) => $query
                    ->where('organization_id', $organization->getKey())
                    ->where('status', MembershipStatus::Active))
                ->lockForUpdate()
                ->firstOrFail();

            if ($person->user_id !== null) {
                throw new DomainRuleViolation(
                    errorCode: 'identity.person_already_linked',
                    message: 'Esta pessoa já possui uma conta vinculada.',
                    httpStatus: 409,
                );
            }

            $email = Str::lower(trim((string) $person->email));

            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                throw new DomainRuleViolation(
                    errorCode: 'identity.person_email_required',
                    message: 'Cadastre um e-mail válido para enviar o convite.',
                );
            }

            PersonAccountInvitation::query()
                ->where('person_id', $person->getKey())
                ->where('status', AccountInvitationStatus::Pending)
                ->where('expires_at', '<=', now())
                ->update(['status' => AccountInvitationStatus::Expired]);

            $pendingInvitationExists = PersonAccountInvitation::query()
                ->where('person_id', $person->getKey())
                ->where('status', AccountInvitationStatus::Pending)
                ->exists();

            if ($pendingInvitationExists) {
                throw new DomainRuleViolation(
                    errorCode: 'identity.account_invitation_already_pending',
                    message: 'Já existe um convite válido para esta pessoa.',
                    httpStatus: 409,
                );
            }

            $plainToken = Str::random(64);
            $expiresAt = now()->addHours(48);
            $invitation = PersonAccountInvitation::query()->create([
                'person_id' => $person->getKey(),
                'organization_id' => $organization->getKey(),
                'invited_by' => $actor->getKey(),
                'email' => $email,
                'token_hash' => hash('sha256', $plainToken),
                'status' => AccountInvitationStatus::Pending,
                'expires_at' => $expiresAt,
            ]);

            $this->audit->record(
                actorUserId: (string) $actor->getKey(),
                organizationId: (string) $organization->getKey(),
                action: AuditAction::AccountInvitationCreated,
                entityType: 'person_account_invitation',
                entityId: (string) $invitation->getKey(),
                previousState: null,
                newState: [
                    'person_id' => (string) $person->getKey(),
                    'status' => AccountInvitationStatus::Pending->value,
                    'expires_at' => $expiresAt->toISOString(),
                ],
            );

            $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
            $acceptanceUrl = "{$frontendUrl}/?invitation={$plainToken}";
            $personName = (string) ($person->preferred_name ?: $person->full_name);

            DB::afterCommit(function () use ($email, $personName, $acceptanceUrl, $expiresAt): void {
                Mail::to($email)->queue(new PersonAccountInvitationMail(
                    personName: $personName,
                    acceptanceUrl: $acceptanceUrl,
                    expiresAt: $expiresAt,
                ));
            });

            return $invitation;
        });
    }
}
