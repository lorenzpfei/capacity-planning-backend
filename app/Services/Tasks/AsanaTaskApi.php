<?php

namespace App\Services\Tasks;

use App\Contracts\TaskService;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class AsanaTaskApi implements TaskService
{

    public static function getAssignedTasksForUser(User $user, string $from = '', string $to = ''): Collection
    {
        return Task::where('task_user_id', '=', $user->task_user_id)->get();
    }

    public static function importTasksForUser(User $user, string $from = '', string $to = ''): int
    {
        $workspaces = self::getWorkspacesForUser($user);

        $userTaskLists = [];
        foreach ($workspaces as $workspace) {
            $userTaskLists[] = self::getUserTaskListsByWorkspace($user, $workspace['gid']);
        }

        $tasks = [];
        foreach ($userTaskLists as $userTaskList) {
            foreach (self::getTasksByUserTaskList($user, $userTaskList['gid']) as $task) {
                $tasks[] = $task;
            }
        }

        $dbData = [];
        foreach ($tasks as $task) {
            $dbTask = [];
            $dbTask['id'] = (int)$task['gid'];
            $dbTask['name'] = $task['name'];
            $dbTask['task_user_id'] = $user->task_user_id;
            $dbTask['completed'] = null;
            if($task['completed_at'] !== null)
            {
                $dbTask['completed'] = $task['completed_at'];
            }

            $dbTask['due'] = null;
            if ($task['due_on'] !== null || $task['due_at'] !== null) {
                $dbTask['due'] = $task['due_on'] ?: $task['due_at'];
            }
            $dbTask['start'] = null;
            if ($task['start_on'] !== null || $task['start_at'] !== null) {
                $dbTask['start'] = $task['start_on'] ?: $task['start_at'];
            }

            $dbTask['custom_fields'] = null;
            $dbTask['priority'] = null;
            if (isset($task['custom_fields']) && count($task['custom_fields']) > 0) {
                $dbTask['custom_fields'] = [];
                foreach ($task['custom_fields'] as $customField) {
                    if ($customField['name'] === 'Priorität') {
                        $dbTask['priority'] = $customField['display_value'];
                    } else {
                        $dbTask['custom_fields'][] = [$customField['name'] => $customField['display_value']];
                    }
                }
                $dbTask['custom_fields'] = json_encode($dbTask['custom_fields']);
            }

            $dbData[] = $dbTask;
        }

        return Task::upsert($dbData,
            'id',
            ['id', 'name', 'task_user_id', 'completed', 'due', 'start', 'custom_fields', 'priority']
        );
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
