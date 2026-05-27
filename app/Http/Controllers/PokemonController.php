<?php

namespace App\Http\Controllers;

use App\Http\Requests\PokemonBattleRequest;
use App\Services\PokemonBattleService;
use App\Services\PokemonService;

class PokemonController extends Controller
{
    public function __construct(
        private readonly PokemonService $pokemonService,
        private readonly PokemonBattleService $pokemonBattleService
    ) {}

    public function cache()
    {
        return response()->json($this->pokemonService->cachePokemons());
    }

    public function show(string $name)
    {
        return response()->json($this->pokemonService->getPokemonsByName($name));
    }

    public function battle(PokemonBattleRequest $request)
    {
        $validated = $request->validated();

        return response()->json(
            $this->pokemonBattleService->battle(
                $validated['pokemon_one'],
                $validated['pokemon_two']
            )
        );
    }
}
