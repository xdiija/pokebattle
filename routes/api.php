<?php

use App\Http\Controllers\PokemonController;
use Illuminate\Support\Facades\Route;

    
Route::prefix('pokemons')->controller(PokemonController::class)->group(function (): void {
    Route::get('/cache', 'cache');
    Route::get('/{name}', 'show');
    Route::post('/battle', 'battle');
});
