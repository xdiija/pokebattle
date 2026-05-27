<?php

namespace App\Services;

use App\DTOs\PokemonDTO;

class PokemonBattleService
{
    public function __construct(
        private readonly PokemonService $pokemonService
    ) {}

    public function battle(string $firstPokemon, string $secondPokemon): array
    {
        $pokemonOne = $this->pokemonService->getPokemonByName($firstPokemon);
        $pokemonTwo = $this->pokemonService->getPokemonByName($secondPokemon);

        return [
            'pokemon_one' => $pokemonOne,
            'pokemon_two' => $pokemonTwo,
            'result' => $this->getBattleResult($pokemonOne, $pokemonTwo),
        ];
    }

    private function getBattleResult(PokemonDTO $pokemonOne, PokemonDTO $pokemonTwo): array
    {
        if ($pokemonOne->hp === $pokemonTwo->hp) {
            return [
                'winner' => null,
                'message' => 'The battle ended in a draw.',
            ];
        }

        $winner = $pokemonOne->hp > $pokemonTwo->hp
            ? $pokemonOne
            : $pokemonTwo;

        return [
            'winner' => $winner->name,
            'message' => "{$winner->name} won the battle with {$winner->hp} HP.",
        ];
    }
}
