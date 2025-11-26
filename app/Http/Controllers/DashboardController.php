<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $teamMembers = User::query()
            ->where('team_id', $user->team_id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'avatar_color',
            ]);

        return Inertia::render('Dashboard', [
            'teamMembers' => $teamMembers,
        ]);
    }
}
