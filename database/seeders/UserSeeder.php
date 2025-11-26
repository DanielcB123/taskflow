<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Team;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $mediahaus = Team::where('slug', 'mediahaus-squad')->first();
        $design    = Team::where('slug', 'design-team')->first();

        // Fallback if teams weren't seeded yet
        if (! $mediahaus) {
            $mediahaus = Team::create([
                'name'  => 'MediaHaus Squad',
                'slug'  => 'mediahaus-squad',
                'color' => '#2563eb',
            ]);
        }

        if (! $design) {
            $design = Team::create([
                'name'  => 'Design Team',
                'slug'  => 'design-team',
                'color' => '#ec4899',
            ]);
        }

        //
        // MediaHaus Squad (3 users)
        //
        User::updateOrCreate(
            ['email' => 'media1@example.com'],
            [
                'name'         => 'Media User 1',
                'password'     => Hash::make('password'),
                'team_id'      => $mediahaus->id,
                'avatar_color' => '#0ea5e9',
            ]
        );

        User::updateOrCreate(
            ['email' => 'media2@example.com'],
            [
                'name'         => 'Media User 2',
                'password'     => Hash::make('password'),
                'team_id'      => $mediahaus->id,
                'avatar_color' => '#22c55e',
            ]
        );

        User::updateOrCreate(
            ['email' => 'media3@example.com'],
            [
                'name'         => 'Media User 3',
                'password'     => Hash::make('password'),
                'team_id'      => $mediahaus->id,
                'avatar_color' => '#f59e0b',
            ]
        );

        //
        // Design Team (2 users)
        //
        User::updateOrCreate(
            ['email' => 'design1@example.com'],
            [
                'name'         => 'Design User 1',
                'password'     => Hash::make('password'),
                'team_id'      => $design->id,
                'avatar_color' => '#ec4899',
            ]
        );

        User::updateOrCreate(
            ['email' => 'design2@example.com'],
            [
                'name'         => 'Design User 2',
                'password'     => Hash::make('password'),
                'team_id'      => $design->id,
                'avatar_color' => '#a855f7',
            ]
        );
    }
}
