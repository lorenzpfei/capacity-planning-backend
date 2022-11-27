<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

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

    public function handleProviderCallback(string $provider): RedirectResponse
    {
        $socialiteUser = Socialite::driver($provider)->user();

        //todo changeme to correct token names
        User::where('email', $socialiteUser->getEmail())
            ->update(['tracking_token' => $socialiteUser->token, 'tracking_refresh_token' => $socialiteUser->refreshToken]);

        //Redirect
        return redirect('');
    }
}
