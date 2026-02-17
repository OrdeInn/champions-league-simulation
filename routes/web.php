<?php

use App\Http\Controllers\FixtureController;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TeamController::class, 'index'])->name('teams.index');

Route::post('/fixtures/generate', [FixtureController::class, 'generate'])->name('fixtures.generate');
Route::get('/fixtures', [FixtureController::class, 'index'])->name('fixtures.index');

Route::get('/simulation', [SimulationController::class, 'index'])->name('simulation.index');
Route::post('/simulation/play-week', [SimulationController::class, 'playWeek'])->name('simulation.play-week');
Route::post('/simulation/play-all', [SimulationController::class, 'playAll'])->name('simulation.play-all');
Route::put('/simulation/matches/{gameMatch}', [SimulationController::class, 'updateMatch'])->name('simulation.update-match');
Route::post('/simulation/reset', [SimulationController::class, 'reset'])->name('simulation.reset');
