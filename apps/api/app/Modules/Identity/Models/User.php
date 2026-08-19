<?php

declare(strict_types=1);

namespace App\Modules\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable;

    protected $guarded = [];

    protected $hidden = ['password_hash', 'remember_token'];

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'last_login_at' => 'immutable_datetime',
            'password_hash' => 'hashed',
        ];
    }

    /** @return BelongsTo<Person, $this> */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** @return HasMany<ParishUserMembership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(ParishUserMembership::class);
    }

    /** @return HasMany<UserExternalIdentity, $this> */
    public function externalIdentities(): HasMany
    {
        return $this->hasMany(UserExternalIdentity::class);
    }
}
