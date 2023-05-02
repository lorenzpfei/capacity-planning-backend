<?php

namespace App\Services\Tasks;

use App\Contracts\TaskService;
use App\Models\Task;
use App\Models\User;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class AsanaTaskApi implements TaskService
{
    /**
     * Get tasks by user
     *
     * @param User $user User to get the tasks for
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @return Collection Task Collection
     */
    public function getAssignedTasksForUser(User $user, DateTime $from = null, DateTime $to = null): Collection
    {
        return Task::where('assigned_user_id', $user->id)->get();
    }

    /**
     * Get asana tasks for specified user and upsert them
     *
     * @param User $user User to import the tasks for
     * @param DateTime|null $from
     * @param DateTime|null $to
     * @return array Task amount
     * @throws Exception
     */
    public function importTasksForUser(User $user, DateTime $from = null, DateTime $to = null): array
    {
        if ($from === null) {
            $from = new DateTime(sprintf('%d-01-01', date("Y")));
        }
        if ($to === null) {
            $to = new DateTime(sprintf('%d-12-31', date("Y")));
        }
        //todo: implement time range


        $workspaces = $this->getWorkspacesForUser($user);

        $tasks = [];
        foreach ($workspaces as $workspace) {
            $userTaskListId = $this->getUserTaskListsByWorkspace($user, $workspace['gid'])['gid'];
            foreach ($this->getTasksByUserTaskList($user, $userTaskListId) as $task) {
                $tasks[] = $task;
            }
        }

        $dbData = [];
        $now = date('Y-m-d H:i:s');
        //Format data for upsert
        foreach ($tasks as $task) {
            $dbTask = [];
            $dbTask['id'] = (int)$task['gid'];
            $dbTask['name'] = $task['name'];
            $dbTask['assigned_user_id'] = $user->id;
            $dbTask['created_at'] = date('Y-m-d H:i:s', strtotime($task['created_at']));

            $creator = $user;
            if (isset($task['created_by']['gid']) && (string)$task['created_by']['gid'] !== (string)$user->task_user_id) {
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

            $dbTask['link'] = $task['permalink_url'];
            $dbTask['updated_at'] = $now;

            $dbData[] = $dbTask;
        }

        $amount = Task::upsert($dbData,
            'id',
            ['id', 'name', 'assigned_user_id', 'created_at', 'creator_user_id', 'completed', 'due', 'start', 'custom_fields', 'priority', 'updated_at']
        );

        //delete tasks which are no longer assigned to the user
        //todo: instead of delete set assigned_user_id = 0
        //todo: change to imported_at because updated_at belongs to asana
        $delete = Task::where('assigned_user_id', $user->id)->where('updated_at', '<', $now)->delete();

        return ['upsert' => $amount, 'delete' => $delete];
    }

    /**
     * Get workspaces for user
     *
     * @param User $user User to get the workspaces for
     * @return array|mixed workspace data or asana error data
     */
    private function getWorkspacesForUser(User $user)
    {
        return $this->get($user, '/workspaces');
    }

    /**
     * Returns asana tasks
     *
     * @param User $user User to get the tasks for
     * @param string $userTaskListId user task list id
     * @return array|mixed workspace data or asana error data
     */
    private function getTasksByUserTaskList(User $user, string $userTaskListId)
    {
        return $this->get($user, '/user_task_lists/' . $userTaskListId . '/tasks?completed_since=now&opt_fields=' . config('services.asana.optfields'));
    }

    /**
     * Return user task lists
     *
     * @param User $user User to get the task list for
     * @param string $workspaceId workspace id
     * @return array|mixed workspace data or asana error data
     */
    private function getUserTaskListsByWorkspace(User $user, string $workspaceId)
    {
        return $this->get($user, '/users/' . $user->task_user_id . '/user_task_list?workspace=' . $workspaceId);
    }

    /**
     * Sends a http request, refreshes token if invalid and sends http request again
     *
     * @param User $user User to use the bearer token from
     * @param string $url relative url to asana api
     * @return array|mixed workspace data or asana error data
     */
    private function get(User $user, string $url)
    {
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . $user->task_token])->get('https://app.asana.com/api/1.0' . $url);
        $respBody = $response->json();

        if (isset($respBody['errors'])) {
            $token = $this->refreshToken($user);
            $response = Http::withHeaders(['Authorization' => 'Bearer ' . $token])->get('https://app.asana.com/api/1.0' . $url);
            $respBody = $response->json();
        }
        return $respBody['data'];
    }

    /**
     * Refreshes the token by using the refresh_token from the user
     *
     * @param User $user User to use the bearer token from
     * @return string token
     */
    private function refreshToken(User $user): string
    {
        $options = [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.asana.client_id'),
            'client_secret' => config('services.asana.client_secret'),
            'redirect_uri' => config('services.asana.redirect'),
            'code' => '',
            'refresh_token' => $user->task_refresh_token
        ];
        $response = Http::post('https://app.asana.com/-/oauth_token?' . http_build_query($options));
        $respBody = $response->json();

        $user->update(['tracking_token' => $respBody['access_token']]);
        return $respBody['access_token'];
    }
}
