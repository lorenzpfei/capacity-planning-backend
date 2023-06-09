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
        try{
            $socialiteUser = Socialite::driver($provider)->stateless()->user();
        }catch(\Exception $e){
            dd($e); //todo: Remove debug
        }

        //check if email domain is on the whitelist
        if(strlen(config('services.security.allowed_login_domains')) > 0)
        {
            $allowedDomains = explode(',', config('services.security.allowed_login_domains'));
            $swag = $socialiteUser->getEmail();

            $splitMail = explode('@', $swag);
            $domainFromMail = end($splitMail);
            if (!in_array($domainFromMail, $allowedDomains)) {
                return redirect('/', 401, ['error', 'Your email domain is not allowed.']);
            }
        }

        $taskProvider = config('services.provider.task');
        $trackingProvider = config('services.provider.tracking');
        $loginProvider = config('services.provider.login');
        $user = User::firstWhere(['email' => $socialiteUser->getEmail()]);

        if($user === null)
        {
            $user = User::newModelInstance();
            $user->email = $socialiteUser->getEmail();
        }


        if ($provider === $taskProvider) {
            $user->task_token = $socialiteUser->token;
            $user->task_refresh_token = $socialiteUser->refreshToken;
            $user->task_user_id = $socialiteUser->getId();
        }
        if ($provider === $trackingProvider) {
            $user->tracking_token = $socialiteUser->token;
            $user->tracking_refresh_token = $socialiteUser->refreshToken;
            $user->tracking_user_id = $socialiteUser->getId();
        }

        if (isset($socialiteUser->getAvatar()["image_128x128"]) && $socialiteUser->getAvatar()["image_128x128"] !== null) {
            $user->avatar = $socialiteUser->getAvatar()["image_128x128"];
        }


        if ($socialiteUser->getName() !== null) {
            $user->name = $socialiteUser->getName();
        }

        $user->save();

        if ($provider === $loginProvider) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        //Redirect
        return redirect(config('services.frontend.url'));
    }
}
