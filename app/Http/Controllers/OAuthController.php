<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;

class OAuthController extends Controller
{
    /**
     * @param string $provider
     * @return RedirectResponse
     */
    public function redirectToProvider(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)->redirect();
    }

    public function handleProviderCallback(string $provider, Request $request): RedirectResponse
    {
        //todo: improve this
        //does not work on twitter
        $socialiteUser = Socialite::driver($provider)->stateless()->user();

        $taskProvider = config('services.provider.task');
        $trackingProvider = config('services.provider.tracking');
        $loginProvider = config('services.provider.login');
        $update = [];
        if ($provider === $taskProvider) {
            $update = [
                'task_token' => $socialiteUser->token,
                'task_refresh_token' => $socialiteUser->refreshToken,
                'task_user_id' => $socialiteUser->getId()
            ];
        }
        if ($provider === $trackingProvider) {
            $update = array_merge($update, [
                'tracking_token' => $socialiteUser->token,
                'tracking_refresh_token' => $socialiteUser->refreshToken,
                'tracking_user_id' => $socialiteUser->getId()
            ]);
        }

        if ($provider === $loginProvider) {
            $update = array_merge($update, [
                'login_token' => $socialiteUser->token
            ]);
        }

        if (isset($socialiteUser->getAvatar()["image_128x128"]) && $socialiteUser->getAvatar()["image_128x128"] !== null) {
            $update = array_merge($update, [
                'avatar' => $socialiteUser->getAvatar()["image_128x128"]
            ]);
        }

        $user = User::updateOrCreate([
            'email' => $socialiteUser->getEmail(),
        ], $update);

        if ($provider === $loginProvider) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        //Redirect
        return redirect(config('services.frontend.url'));
    }
}
