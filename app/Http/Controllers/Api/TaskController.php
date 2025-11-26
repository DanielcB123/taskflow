<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskTag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $teamId = $user->team_id;

        $assignedTo = $request->input('assigned_to'); // optional user id
        $search     = $request->input('search');

        $query = Task::with(['assignedUser', 'creator', 'tags'])
            ->where('team_id', $teamId)
            ->orderBy('status')
            ->orderBy('position');

        if ($assignedTo === 'me') {
            $query->where('assigned_to', $user->id);
        } elseif (!empty($assignedTo)) {
            $query->where('assigned_to', $assignedTo);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $tasks = $query->get();

        return response()->json([
            'success' => true,
            'tasks'   => $tasks,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            'priority'    => ['required', Rule::in(['low', 'medium', 'high'])],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'tags'        => ['array'],
            'tags.*.name' => ['required', 'string', 'max:50'],
            'tags.*.color'=> ['required', 'string', 'max:20'],
        ]);

        $teamId = $user->team_id;

        $maxPosition = Task::where('team_id', $teamId)
            ->where('status', $data['status'])
            ->max('position');

        $task = Task::create([
            'team_id'     => $teamId,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'],
            'priority'    => $data['priority'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by'  => $user->id,
            'position'    => ($maxPosition ?? 0) + 1,
            'completed_at'=> $data['status'] === 'done' ? Carbon::now() : null,
        ]);

        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $tag) {
                TaskTag::create([
                    'task_id' => $task->id,
                    'name'    => $tag['name'],
                    'color'   => $tag['color'],
                ]);
            }
        }

        $task->load(['assignedUser', 'creator', 'tags']);

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'task'    => $task,
        ]);
    }

    public function update(Request $request, Task $task)
    {
        $user = $request->user();

        if ($task->team_id !== $user->team_id) {
            return response()->json([
                'success' => false,
                'error'   => 'You cannot modify tasks from another team.',
            ], 200);
        }

        $data = $request->validate([
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'priority'    => ['sometimes', Rule::in(['low', 'medium', 'high'])],
            'status'      => ['sometimes', Rule::in(['todo', 'in_progress', 'done'])],
            'tags'        => ['sometimes', 'array'],
            'tags.*.name' => ['required_with:tags', 'string', 'max:50'],
            'tags.*.color'=> ['required_with:tags', 'string', 'max:20'],
        ]);

        $oldStatus = $task->status;

        $task->fill($data);

        if (array_key_exists('status', $data)) {
            if ($data['status'] === 'done' && !$task->completed_at) {
                $task->completed_at = Carbon::now();
            }
            if ($data['status'] !== 'done') {
                $task->completed_at = null;
            }
        }

        $task->save();

        if (array_key_exists('tags', $data)) {
            $task->tags()->delete();
            foreach ($data['tags'] as $tag) {
                TaskTag::create([
                    'task_id' => $task->id,
                    'name'    => $tag['name'],
                    'color'   => $tag['color'],
                ]);
            }
        }

        $task->load(['assignedUser', 'creator', 'tags']);

        return response()->json([
            'success'           => true,
            'message'           => 'Task updated successfully',
            'task'              => $task,
            'status_changed_to' => $task->status !== $oldStatus ? $task->status : null,
        ]);
    }

    public function move(Request $request, Task $task)
    {
        $user = $request->user();

        if ($task->team_id !== $user->team_id) {
            return response()->json([
                'success' => false,
                'error'   => 'You cannot move tasks from another team.',
            ], 200);
        }

        $data = $request->validate([
            'status'   => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            'position' => ['required', 'integer', 'min:1'],
        ]);

        $oldStatus = $task->status;

        $task->status = $data['status'];

        // Shift positions in new column
        Task::where('team_id', $task->team_id)
            ->where('status', $data['status'])
            ->where('id', '!=', $task->id)
            ->where('position', '>=', $data['position'])
            ->increment('position');

        $task->position = $data['position'];

        if ($data['status'] === 'done' && !$task->completed_at) {
            $task->completed_at = Carbon::now();
        }
        if ($data['status'] !== 'done') {
            $task->completed_at = null;
        }

        $task->save();

        $task->load(['assignedUser', 'creator', 'tags']);

        return response()->json([
            'success'           => true,
            'message'           => 'Task moved successfully',
            'task'              => $task,
            'status_changed_to' => $task->status !== $oldStatus ? $task->status : null,
        ]);
    }

    public function destroy(Request $request, Task $task)
    {
        $user = $request->user();

        if ($task->team_id !== $user->team_id) {
            return response()->json([
                'success' => false,
                'error'   => 'You cannot delete tasks from another team.',
            ], 200);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted with a smooth goodbye.',
        ]);
    }

    public function assign(Request $request, Task $task)
    {
        $user = $request->user();

        if ($task->team_id !== $user->team_id) {
            return response()->json([
                'success' => false,
                'error'   => 'You cannot assign tasks from another team.',
            ], 200);
        }

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (!empty($data['user_id'])) {
            $assignee = User::where('team_id', $task->team_id)
                ->where('id', $data['user_id'])
                ->first();

            if (!$assignee) {
                return response()->json([
                    'success' => false,
                    'error'   => 'User must belong to the same team.',
                ], 200);
            }

            $task->assigned_to = $assignee->id;
        } else {
            // Unassign
            $task->assigned_to = null;
        }

        $task->save();
        $task->load(['assignedUser', 'creator', 'tags']);

        return response()->json([
            'success' => true,
            'message' => 'Task assignment updated.',
            'task'    => $task,
        ]);
    }
}
