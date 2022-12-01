<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    /** @var string[]  */
    private array $taskProviders = ['asana'];

    /** @var string[]  */
    private array $trackingProviders = ['everhour'];

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

        $update = [];
        if(in_array($provider, $this->taskProviders, true))
        {
            $update = [
                'task_token' => $socialiteUser->token,
                'task_refresh_token' => $socialiteUser->refreshToken,
                'task_user_id' => $socialiteUser->getId()
            ];
        }
        if(in_array($provider, $this->trackingProviders, true))
        {
            $update = array_merge($update, [
                'tracking_token' => $socialiteUser->token,
                'tracking_refresh_token' => $socialiteUser->refreshToken,
                'tracking_user_id' => $socialiteUser->getId()
            ]);
        }

        User::where('email', $socialiteUser->getEmail())
            ->update($update);

        //Redirect
        return redirect('');
    }
}
