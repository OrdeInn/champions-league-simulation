<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::query()
            ->orderByDesc('power')
            ->get(['id', 'name', 'short_name', 'power']);

        return Inertia::render('Teams/Index', [
            'teams' => $teams,
        ]);
    }
}
