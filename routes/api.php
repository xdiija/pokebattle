<?php

use App\Http\Controllers\PokemonBattleController;
use Illuminate\Support\Facades\Route;

Route::prefix('poke-battle')->controller(PokemonBattleController::class)->group(function (): void {
    Route::get('/', 'index');
    Route::post('/', 'battle');
});