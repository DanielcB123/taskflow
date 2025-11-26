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

        if (! $mediahaus) {
            $mediahaus = Team::create([
                'name'  => 'MediaHaus Squad',
                'slug'  => 'mediahaus-squad',
                'color' => '#2563eb',
            ]);
        }

        User::updateOrCreate(
            ['email' => 'demo1@example.com'],
            [
                'name'         => 'Demo User 1',
                'password'     => Hash::make('password'),
                'team_id'      => $mediahaus->id,
                'avatar_color' => '#0ea5e9',
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo2@example.com'],
            [
                'name'         => 'Demo User 2',
                'password'     => Hash::make('password'),
                'team_id'      => $mediahaus->id,
                'avatar_color' => '#22c55e',
            ]
        );
    }
}
