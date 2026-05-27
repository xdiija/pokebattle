<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PokemonBattleTest extends TestCase
{
    public function test_pokemon_with_higher_hp_wins_battle(): void
    {
        $this->fakePokemons([
            'pikachu' => $this->pokeApiPokemon('pikachu', 35, 'pikachu.png'),
            'charizard' => $this->pokeApiPokemon('charizard', 78, 'charizard.png'),
        ]);

        $response = $this->postJson('/api/pokemons/battle', [
            'pokemon_one' => 'pikachu',
            'pokemon_two' => 'charizard',
        ]);

        $response->assertOk()
            ->assertJsonPath('pokemon_one.name', 'pikachu')
            ->assertJsonPath('pokemon_one.hp', 35)
            ->assertJsonPath('pokemon_one.image', 'pikachu.png')
            ->assertJsonPath('pokemon_two.name', 'charizard')
            ->assertJsonPath('pokemon_two.hp', 78)
            ->assertJsonPath('result.winner', 'charizard')
            ->assertJsonPath('result.message', 'charizard won the battle with 78 HP.');
    }

    public function test_battle_can_end_in_draw(): void
    {
        $this->fakePokemons([
            'pikachu' => $this->pokeApiPokemon('pikachu', 35),
            'ditto' => $this->pokeApiPokemon('ditto', 35),
        ]);

        $response = $this->postJson('/api/pokemons/battle', [
            'pokemon_one' => 'pikachu',
            'pokemon_two' => 'ditto',
        ]);

        $response->assertOk()
            ->assertJsonPath('result.winner', null)
            ->assertJsonPath('result.message', 'The battle ended in a draw.');
    }

    public function test_battle_returns_clear_error_when_pokemon_does_not_exist(): void
    {
        config(['services.pokeapi.base_url' => 'https://pokeapi.test']);

        Http::fake([
            'pokeapi.test/pokemon/pikachu' => Http::response($this->pokeApiPokemon('pikachu', 35)),
            'pokeapi.test/pokemon/missingno' => Http::response([], 404),
        ]);

        $response = $this->postJson('/api/pokemons/battle', [
            'pokemon_one' => 'pikachu',
            'pokemon_two' => 'missingno',
        ]);

        $response->assertNotFound()
            ->assertJsonPath('message', "Pokemon 'missingno' not found.");
    }

    public function test_battle_validates_required_pokemon_names(): void
    {
        $response = $this->postJson('/api/pokemons/battle', [
            'pokemon_one' => 'pi',
            'pokemon_two' => '',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['pokemon_one', 'pokemon_two'])
            ->assertJsonPath('errors.pokemon_one.0', 'O primeiro Pokemon deve ter pelo menos 3 caracteres.')
            ->assertJsonPath('errors.pokemon_two.0', 'Informe o segundo Pokemon.');
    }

    public function test_battle_validates_pokemon_names_are_different(): void
    {
        $response = $this->postJson('/api/pokemons/battle', [
            'pokemon_one' => 'pikachu',
            'pokemon_two' => 'pikachu',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['pokemon_two'])
            ->assertJsonPath('errors.pokemon_two.0', 'Os Pokemons da batalha devem ser diferentes.');
    }

    private function fakePokemons(array $pokemons): void
    {
        config(['services.pokeapi.base_url' => 'https://pokeapi.test']);

        $responses = [];

        foreach ($pokemons as $name => $pokemon) {
            $responses["pokeapi.test/pokemon/{$name}"] = Http::response($pokemon);
        }

        Http::fake($responses);
    }

    private function pokeApiPokemon(
        string $name,
        int $hp,
        ?string $image = null
    ): array
    {
        return [
            'name' => $name,
            'stats' => [
                [
                    'base_stat' => $hp,
                    'stat' => [
                        'name' => 'hp',
                    ],
                ],
            ],
            'sprites' => [
                'front_default' => $image,
            ],
        ];
    }
}
