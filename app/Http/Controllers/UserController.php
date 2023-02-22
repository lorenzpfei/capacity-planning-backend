<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Returns names of the providers which oauth routes are available and the corresponding bool if they are also authenticated for the user
     *
     * @return string[]
     */
    public function getAvailableOAuthProviders(Request $request)
    {
        $availableProviders = explode(',', config('services.provider.connectable'));
        $trackingProvider = config('services.provider.tracking');
        $taskProvider = config('services.provider.task');

        $oauthStates = [];
        $user = $request->user();
        if(in_array($trackingProvider, $availableProviders, true))
        {
            $state = new \stdClass();
            $state->name = $trackingProvider;
            $state->connected = ($user->tracking_user_id || $user->tracking_token || $user->tracking_refresh_token);
            $oauthStates[] = $state;
        }
        if(in_array($taskProvider, $availableProviders, true))
        {
            $state = new \stdClass();
            $state->name = $taskProvider;
            $state->connected = ($user->task_user_id || $user->task_token || $user->task_refresh_token);
            $oauthStates[] = $state;
        }
        return $oauthStates;
    }

    public function getLoggedinUserData(Request $request){
        return $request->user()->makeHidden(['login_token', 'login_refresh_token', 'task_token', 'task_refresh_token', 'tracking_refresh_token', 'tracking_token', 'task_user_id', 'tracking_user_id']);
    }

    /**
     * Logs current user out
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        if(method_exists(auth()->user()?->currentAccessToken(), 'delete')) {
            auth()->user()?->currentAccessToken()->delete();
        }

        auth()->guard('web')->logout();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}
