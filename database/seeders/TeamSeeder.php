<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            [
                'slug'  => 'mediahaus-squad',
                'name'  => 'MediaHaus Squad',
                'color' => '#2563eb',
            ],
            [
                'slug'  => 'design-team',
                'name'  => 'Design Team',
                'color' => '#ec4899',
            ],
        ];

        foreach ($teams as $data) {
            Team::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name'], 'color' => $data['color']]
            );
        }
    }
}
