<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $mediahaus = Team::where('slug', 'mediahaus-squad')->first();
        $design    = Team::where('slug', 'design-team')->first();

        if (! $mediahaus || ! $design) {
            $this->command?->warn('Teams not found, make sure TeamSeeder ran first.');
            return;
        }

        $mediaUsers  = User::where('team_id', $mediahaus->id)->get();
        $designUsers = User::where('team_id', $design->id)->get();

        $media1 = $mediaUsers->firstWhere('email', 'media1@example.com') ?? $mediaUsers->first();
        $media2 = $mediaUsers->firstWhere('email', 'media2@example.com');
        $media3 = $mediaUsers->firstWhere('email', 'media3@example.com');

        $design1 = $designUsers->firstWhere('email', 'design1@example.com') ?? $designUsers->first();
        $design2 = $designUsers->firstWhere('email', 'design2@example.com');

        // Simple position counters per team + status
        $positions = [];

        $tasks = [
            // MediaHaus Squad
            [
                'team'       => $mediahaus,
                'title'      => 'Set up TaskFlow project structure',
                'description'=> 'Scaffold Laravel backend and Vue 3 frontend, configure Inertia and Tailwind.',
                'status'     => 'todo',
                'priority'   => 'high',
                'assignee'   => $media1,
                'creator'    => $media1,
            ],
            [
                'team'       => $mediahaus,
                'title'      => 'Design Kanban board layout',
                'description'=> 'Create a responsive three column layout with smooth drag and drop interactions.',
                'status'     => 'todo',
                'priority'   => 'medium',
                'assignee'   => $media2,
                'creator'    => $media1,
            ],
            [
                'team'       => $mediahaus,
                'title'      => 'Implement custom API key auth',
                'description'=> 'Add API key issuance endpoint and middleware for protecting task routes.',
                'status'     => 'in_progress',
                'priority'   => 'high',
                'assignee'   => $media1,
                'creator'    => $media1,
            ],
            [
                'team'       => $mediahaus,
                'title'      => 'Hook up drag and drop reordering',
                'description'=> 'Persist column and position changes when a task is moved.',
                'status'     => 'in_progress',
                'priority'   => 'medium',
                'assignee'   => $media2,
                'creator'    => $media1,
            ],
            [
                'team'       => $mediahaus,
                'title'      => 'Add command palette',
                'description'=> 'Quick actions for creating tasks, filtering, and jumping to columns.',
                'status'     => 'in_progress',
                'priority'   => 'low',
                'assignee'   => $media3,
                'creator'    => $media1,
            ],
            [
                'team'       => $mediahaus,
                'title'      => 'Polish micro interactions',
                'description'=> 'Add hover states, subtle animations, and confetti on key actions.',
                'status'     => 'done',
                'priority'   => 'low',
                'assignee'   => $media2,
                'creator'    => $media1,
                'completed'  => true,
            ],
            [
                'team'       => $mediahaus,
                'title'      => 'Seed demo data for tasks and tags',
                'description'=> 'Provide out of the box demo tasks for MediaHaus review.',
                'status'     => 'done',
                'priority'   => 'medium',
                'assignee'   => $media3,
                'creator'    => $media1,
                'completed'  => true,
            ],

            // Design Team
            [
                'team'       => $design,
                'title'      => 'Create TaskFlow logo concepts',
                'description'=> 'Explore three logo options that fit the MediaHaus visual system.',
                'status'     => 'todo',
                'priority'   => 'medium',
                'assignee'   => $design1,
                'creator'    => $design1,
            ],
            [
                'team'       => $design,
                'title'      => 'Define color system and tokens',
                'description'=> 'Primary, accent, and semantic colors for tasks and priorities.',
                'status'     => 'in_progress',
                'priority'   => 'high',
                'assignee'   => $design2,
                'creator'    => $design1,
            ],
            [
                'team'       => $design,
                'title'      => 'Finalize typography scale',
                'description'=> 'Heading, body, and microcopy sizes for the dashboard.',
                'status'     => 'done',
                'priority'   => 'low',
                'assignee'   => $design1,
                'creator'    => $design1,
                'completed'  => true,
            ],
        ];

        foreach ($tasks as $data) {
            /** @var \App\Models\Team $team */
            $team = $data['team'];

            $status   = $data['status'];
            $priority = $data['priority'];
            $creator  = $data['creator'] ?? $media1;

            $positionsKey = $team->id . ':' . $status;
            $positions[$positionsKey] = ($positions[$positionsKey] ?? 0) + 1;

            $task = Task::create([
                'team_id'      => $team->id,
                'title'        => $data['title'],
                'description'  => $data['description'],
                'status'       => $status,
                'priority'     => $priority,
                'assigned_to'  => $data['assignee']?->id,
                'created_by'   => $creator?->id,
                'position'     => $positions[$positionsKey],
                'completed_at' => !empty($data['completed'])
                    ? Carbon::now()->subDays(rand(1, 5))
                    : null,
            ]);
        }
    }
}
