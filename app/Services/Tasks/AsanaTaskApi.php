<?php

namespace App\Services\Tasks;

use App\Contracts\TaskService;
use App\Models\User;
use Asana\Client;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

class AsanaTaskApi implements TaskService
{

    public static function getAssignedTasksForUser(User $user, string $from = '', string $to = ''): array
    {
        if(strlen($user->tracking_token) === 0){
            dd('no tracking token provided');
        }
        //todo changeme to correct token names
        $options = [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.asana.client_id'),
            'client_secret' => config('services.asana.client_secret'),
            'redirect_uri' => config('services.asana.redirect'),
            'code' => '',
            'refresh_token' => $user->tracking_refresh_token
        ];

        $response = Http::post('https://app.asana.com/-/oauth_token?'.http_build_query($options),);
        $respBody = $response->json();

        $user->update(['tracking_token' => $respBody['access_token']]);

        $response = Http::withHeaders(['Authorization' => 'Bearer '.$respBody['access_token']])->get('https://app.asana.com/api/1.0/users/' . $user['email'] . '/user_task_list?workspace=' . '1114408007452809');
        $respBody = $response->json();
        dd($respBody);
        $userTaskList = $asanaUserClient->get('https://app.asana.com/api/1.0/users/' . $user['email'] . '/user_task_list?workspace=' . '1114408007452809', []);
        return $asanaUserClient->get('/user_task_lists/' . $userTaskList->gid . '/tasks?completed_since=now&opt_fields=name,completed_at,start_on,due_on,due_at,start_at,custom_fields', []);
    }
}
