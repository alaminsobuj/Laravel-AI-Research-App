<?php

use App\Http\Controllers\AgentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AgentController::class, 'index'])->name('agents.index');
Route::post('/generate', [AgentController::class, 'generate'])->name('agents.generate');
