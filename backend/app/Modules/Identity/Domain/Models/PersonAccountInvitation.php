<?php

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Identity\Domain\Enums\AccountInvitationStatus;
use App\Modules\Organizations\Domain\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'person_id',
    'organization_id',
    'invited_by',
    'email',
    'token_hash',
    'status',
    'expires_at',
    'accepted_by_user_id',
    'accepted_at',
])]
#[Hidden(['token_hash'])]
final class PersonAccountInvitation extends Model
{
    use HasUlids;

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** @return BelongsTo<User, $this> */
    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function expiresAt(): CarbonImmutable
    {
        $expiresAt = $this->getAttribute('expires_at');

        return $expiresAt instanceof CarbonImmutable
            ? $expiresAt
            : CarbonImmutable::parse((string) $expiresAt);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => AccountInvitationStatus::class,
            'expires_at' => 'immutable_datetime',
            'accepted_at' => 'immutable_datetime',
        ];
    }
}
