<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Identity\Actions\AuthenticateGoogleUser;
use App\Modules\Identity\Exceptions\GoogleAuthenticationFailed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class GoogleSessionController extends Controller
{
    public function redirect(): RedirectResponse|Response
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return response('Login com Google ainda não foi configurado neste ambiente.', 503);
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(AuthenticateGoogleUser $authenticateGoogleUser): RedirectResponse
    {
        $webUrl = rtrim((string) config('app.web_url'), '/');

        try {
            $user = $authenticateGoogleUser->execute(Socialite::driver('google')->user());
            Auth::login($user);
            request()->session()->regenerate();

            return redirect()->away($webUrl.'/?auth=google');
        } catch (GoogleAuthenticationFailed) {
            return redirect()->away($webUrl.'/?auth_error=access_not_authorized');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->away($webUrl.'/?auth_error=provider_failure');
        }
    }
}
