<?php

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\DTOs\AcceptPersonAccountInvitationData;
use App\Modules\Identity\Domain\Enums\AccountInvitationStatus;
use App\Modules\Identity\Domain\Models\Person;
use App\Modules\Identity\Domain\Models\PersonAccountInvitation;
use App\Modules\Identity\Domain\Models\User;
use App\Shared\Auditing\AuditAction;
use App\Shared\Auditing\AuditRecorder;
use App\Shared\Domain\Exceptions\DomainRuleViolation;
use Illuminate\Support\Facades\DB;

final readonly class AcceptPersonAccountInvitation
{
    public function __construct(private AuditRecorder $audit) {}

    public function execute(AcceptPersonAccountInvitationData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $invitation = PersonAccountInvitation::query()
                ->where('token_hash', hash('sha256', $data->token))
                ->lockForUpdate()
                ->first();

            if (
                $invitation === null
                || $invitation->status !== AccountInvitationStatus::Pending
                || $invitation->expiresAt()->isPast()
            ) {
                throw new DomainRuleViolation(
                    errorCode: 'identity.account_invitation_invalid',
                    message: 'Este convite é inválido, expirou ou já foi utilizado.',
                );
            }

            $person = Person::query()->whereKey($invitation->person_id)->lockForUpdate()->firstOrFail();

            if ($person->user_id !== null) {
                throw new DomainRuleViolation(
                    errorCode: 'identity.person_already_linked',
                    message: 'Esta pessoa já possui uma conta vinculada.',
                    httpStatus: 409,
                );
            }

            if (User::query()->where('email', $invitation->email)->exists()) {
                throw new DomainRuleViolation(
                    errorCode: 'identity.invitation_email_already_registered',
                    message: 'Já existe uma conta para o e-mail deste convite.',
                    httpStatus: 409,
                );
            }

            $user = User::query()->create([
                'name' => $data->name,
                'email' => $invitation->email,
                'password' => $data->password,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $person->forceFill(['user_id' => $user->getKey()])->save();

            $invitation->forceFill([
                'status' => AccountInvitationStatus::Accepted,
                'accepted_by_user_id' => $user->getKey(),
                'accepted_at' => now(),
            ])->save();

            $this->audit->record(
                actorUserId: (string) $user->getKey(),
                organizationId: (string) $invitation->organization_id,
                action: AuditAction::PersonAccountLinked,
                entityType: 'person',
                entityId: (string) $person->getKey(),
                previousState: ['user_id' => null],
                newState: ['user_id' => (string) $user->getKey()],
            );

            return $user;
        });
    }
}
