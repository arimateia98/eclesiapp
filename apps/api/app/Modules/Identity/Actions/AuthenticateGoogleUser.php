<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Exceptions\GoogleAuthenticationFailed;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserExternalIdentity;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Two\User as SocialiteUser;

final class AuthenticateGoogleUser
{
    public function execute(SocialiteUserContract $googleUser): User
    {
        if (! $googleUser instanceof SocialiteUser) {
            throw new GoogleAuthenticationFailed('O provedor retornou uma identidade incompatível.');
        }

        $subject = $googleUser->getId();
        $email = $googleUser->getEmail();
        $emailVerified = filter_var($googleUser->user['verified_email'] ?? false, FILTER_VALIDATE_BOOL);

        if ($subject === '' || ! is_string($email) || $email === '' || ! $emailVerified) {
            throw new GoogleAuthenticationFailed('A identidade retornada pelo Google não possui e-mail verificado.');
        }

        return DB::transaction(function () use ($subject, $email): User {
            $identity = UserExternalIdentity::query()
                ->where('provider', 'google')
                ->where('provider_subject', $subject)
                ->lockForUpdate()
                ->first();

            if ($identity instanceof UserExternalIdentity) {
                $user = $identity->user;
            } else {
                $user = User::query()
                    ->where('login_email', $email)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $user || $user->status !== 'ACTIVE') {
                throw new GoogleAuthenticationFailed('Conta não autorizada para acesso com Google.');
            }

            if (! $identity) {
                $identity = UserExternalIdentity::query()->create([
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'provider_subject' => $subject,
                    'provider_email' => $email,
                ]);
            }

            $identity->forceFill(['provider_email' => $email, 'last_used_at' => now()])->save();
            $user->forceFill(['last_login_at' => now()])->save();

            return $user;
        });
    }
}
