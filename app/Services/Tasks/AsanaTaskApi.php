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
        $workspaces = self::getWorkspacesForUser($user);

        $userTaskLists = [];
        foreach ($workspaces as $workspace) {
            $userTaskLists[] = self::getUserTaskListsByWorkspace($user, $workspace['gid']);
        }

        $tasks = [];
        foreach ($userTaskLists as $userTaskList) {
            $tasks[] = self::getTasksByUserTaskList($user, $userTaskList['gid']);
        }

        dd($tasks);
        return $asanaUserClient->get('/user_task_lists/' . $userTaskList->gid . '/tasks?completed_since=now&opt_fields=name,completed_at,start_on,due_on,due_at,start_at,custom_fields', []);
    }

    public static function getWorkspacesForUser(User $user)
    {
        return self::get('/workspaces', $user);
    }

    private static function getTasksByUserTaskList(User $user, string $userTaskListId)
    {
        return self::get('/user_task_lists/' . $userTaskListId . '/tasks?completed_since=now&opt_fields=' . config('services.asana.optfields'), $user);
    }

    private static function getUserTaskListsByWorkspace(User $user, string $workspaceId)
    {
        return self::get('/users/' . $user->task_user_id . '/user_task_list?workspace=' . $workspaceId, $user);
    }

    /**
     * @param string $url
     * @param User $user
     * @return array|mixed
     */
    private static function get(string $url, User $user)
    {
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $user->task_token])->get('https://app.asana.com/api/1.0' . $url);
        $respBody = $response->json();
        if (isset($respBody['errors'])) {
            $token = self::refreshToken($user);
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get('https://app.asana.com/api/1.0' . $url);
            $respBody = $response->json();
        }
        return $respBody['data'];
    }

    /**
     * Refreshes the token by using the refresh_token from the user
     *
     * @param User $user
     * @return string
     */
    private static function refreshToken(User $user): string
    {
        $options = [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.asana.client_id'),
            'client_secret' => config('services.asana.client_secret'),
            'redirect_uri' => config('services.asana.redirect'),
            'code' => '',
            'refresh_token' => $user->task_refresh_token
        ];
        $response = Http::post('https://app.asana.com/-/oauth_token?' . http_build_query($options),);
        $respBody = $response->json();

        $user->update(['tracking_token' => $respBody['access_token']]);
        return $respBody['access_token'];
    }
}
