<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Exceptions\GoogleAuthenticationFailed;
use App\Modules\Identity\Models\Person;
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
        $name = $googleUser->getName();
        $emailVerified = filter_var($googleUser->user['verified_email'] ?? false, FILTER_VALIDATE_BOOL);

        if ($subject === '' || ! is_string($email) || $email === '' || ! $emailVerified) {
            throw new GoogleAuthenticationFailed('A identidade retornada pelo Google não possui e-mail verificado.');
        }

        return DB::transaction(function () use ($subject, $email, $name): User {
            $normalizedEmail = mb_strtolower(trim($email));
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['google:'.$subject]);
            DB::select('SELECT pg_advisory_xact_lock(hashtextextended(?, 0))', ['email:'.$normalizedEmail]);

            $identity = UserExternalIdentity::query()
                ->where('provider', 'google')
                ->where('provider_subject', $subject)
                ->lockForUpdate()
                ->first();

            if ($identity instanceof UserExternalIdentity) {
                $user = $identity->user;
            } else {
                $user = User::query()
                    ->where('login_email', $normalizedEmail)
                    ->lockForUpdate()
                    ->first();
            }

            if (! $user) {
                $fullName = is_string($name) && trim($name) !== '' ? trim($name) : $normalizedEmail;
                $person = Person::query()->create([
                    'full_name' => $fullName,
                    'email' => $normalizedEmail,
                ]);
                $user = User::query()->create([
                    'person_id' => $person->id,
                    'login_email' => $normalizedEmail,
                    'auth_provider' => 'GOOGLE',
                    'status' => 'ACTIVE',
                    'email_verified_at' => now(),
                ]);
            }

            if (in_array($user->status, ['BLOCKED', 'DISABLED'], true)) {
                throw new GoogleAuthenticationFailed('Conta não autorizada para acesso com Google.');
            }

            if (! $identity) {
                $existingGoogleIdentity = UserExternalIdentity::query()
                    ->where('user_id', $user->id)
                    ->where('provider', 'google')
                    ->lockForUpdate()
                    ->first();

                if ($existingGoogleIdentity) {
                    throw new GoogleAuthenticationFailed('Conta Google incompatível com o vínculo existente.');
                }

                $identity = UserExternalIdentity::query()->create([
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'provider_subject' => $subject,
                    'provider_email' => $normalizedEmail,
                ]);
            }

            $identity->forceFill(['provider_email' => $normalizedEmail, 'last_used_at' => now()])->save();
            $user->forceFill([
                'status' => 'ACTIVE',
                'email_verified_at' => $user->email_verified_at ?? now(),
                'last_login_at' => now(),
            ])->save();

            return $user;
        });
    }
}
