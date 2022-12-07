<?php

namespace App\Services\Tasks;

use App\Contracts\TaskService;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class AsanaTaskApi implements TaskService
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }


    public function getAssignedTasksForUser(string $from = '', string $to = ''): Collection
    {
        return Task::where('assigned_user_id', $this->user->task_user_id)->get();
    }

    public function importTasksForUser(string $from = '', string $to = ''): int
    {
        $workspaces = $this->getWorkspacesForUser();

        $tasks = [];
        foreach ($workspaces as $workspace) {
            $userTaskListId = $this->getUserTaskListsByWorkspace($workspace['gid'])['gid'];
            foreach ($this->getTasksByUserTaskList($userTaskListId) as $task) {
                $tasks[] = $task;
            }
        }

        $dbData = [];
        foreach ($tasks as $task) {
            $dbTask = [];
            $dbTask['id'] = (int)$task['gid'];
            $dbTask['name'] = $task['name'];
            $dbTask['assigned_user_id'] = $this->user->id;
            $dbTask['created_at'] = date('Y-m-d H:i:s', strtotime($task['created_at']));

            $creator = $this->user;
            if((string)$task['created_by']['gid'] !== (string)$this->user->task_user_id) {
                $creator = User::firstWhere('task_user_id', $task['created_by']['gid']);
            }
            $dbTask['creator_user_id'] = $creator?->id;
            $dbTask['completed'] = null;
            if ($task['completed_at'] !== null) {
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
            ['id', 'name', 'assigned_user_id', 'created_at', 'creator_user_id', 'completed', 'due', 'start', 'custom_fields', 'priority']
        );
    }

    public function getWorkspacesForUser()
    {
        return $this->get('/workspaces');
    }

    private function getTasksByUserTaskList(string $userTaskListId)
    {
        return $this->get('/user_task_lists/' . $userTaskListId . '/tasks?completed_since=now&opt_fields=' . config('services.asana.optfields'));
    }

    private function getUserTaskListsByWorkspace(string $workspaceId)
    {
        return $this->get('/users/' . $this->user->task_user_id . '/user_task_list?workspace=' . $workspaceId);
    }

    /**
     * @param string $url
     * @return array|mixed
     */
    private function get(string $url)
    {
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $this->user->task_token])->get('https://app.asana.com/api/1.0' . $url);
        $respBody = $response->json();

        if (isset($respBody['errors'])) {
            $token = $this->refreshToken();
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get('https://app.asana.com/api/1.0' . $url);
            $respBody = $response->json();
        }
        return $respBody['data'];
    }

    /**
     * Refreshes the token by using the refresh_token from the user
     *
     * @return string
     */
    private function refreshToken(): string
    {
        $options = [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.asana.client_id'),
            'client_secret' => config('services.asana.client_secret'),
            'redirect_uri' => config('services.asana.redirect'),
            'code' => '',
            'refresh_token' => $this->user->task_refresh_token
        ];
        $response = Http::post('https://app.asana.com/-/oauth_token?' . http_build_query($options));
        $respBody = $response->json();

        $this->user->update(['tracking_token' => $respBody['access_token']]);
        return $respBody['access_token'];
    }
}
