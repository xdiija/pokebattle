<?php

namespace Tests\Unit;

use App\Integrations\PokeApi\PokeApiClient;
use App\Services\PokemonService;
use Illuminate\Support\Facades\Redis;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PokemonServiceTest extends TestCase
{
    public function test_get_pokemon_by_name_returns_pokemon_dto(): void
    {
        $pokeApiClient = Mockery::mock(PokeApiClient::class);
        $pokeApiClient->shouldReceive('findPokemon')
            ->once()
            ->with('pikachu')
            ->andReturn($this->pokeApiPokemon('pikachu', 35, 'pikachu.png'));

        $pokemon = new PokemonService($pokeApiClient)->getPokemonByName(' Pikachu ');

        $this->assertSame('pikachu', $pokemon->name);
        $this->assertSame(35, $pokemon->hp);
        $this->assertSame('pikachu.png', $pokemon->image);
    }

    public function test_get_pokemon_by_name_normalizes_spaces_underscores_and_accents(): void
    {
        $pokeApiClient = Mockery::mock(PokeApiClient::class);
        $pokeApiClient->shouldReceive('findPokemon')
            ->once()
            ->with('flabebe-blue')
            ->andReturn($this->pokeApiPokemon('flabebe-blue', 44));

        $pokemon = new PokemonService($pokeApiClient)->getPokemonByName(' Flabébé_Blue ');

        $this->assertSame('flabebe-blue', $pokemon->name);
    }

    public function test_get_pokemons_by_name_uses_soundex_match(): void
    {
        Redis::shouldReceive('hget')
            ->once()
            ->with('pokeBattle:pokemons:idx', 'picachu')
            ->andReturn(null);

        Redis::shouldReceive('hkeys')
            ->once()
            ->with('pokeBattle:pokemons:idx')
            ->andReturn([]);

        Redis::shouldReceive('hget')
            ->once()
            ->with('pokeBattle:pokemons:soundex', soundex('picachu'))
            ->andReturn(json_encode(['pikachu']));

        Redis::shouldReceive('hget')
            ->once()
            ->with('pokeBattle:pokemons:idx', 'pikachu')
            ->andReturn(json_encode([
                'name' => 'pikachu',
                'url' => 'https://pokeapi.co/api/v2/pokemon/25/',
            ]));

        $pokeApiClient = Mockery::mock(PokeApiClient::class);
        $pokeApiClient->shouldReceive('findPokemon')
            ->once()
            ->with('pikachu')
            ->andReturn($this->pokeApiPokemon('pikachu', 35, 'pikachu.png'));

        $pokemons = new PokemonService($pokeApiClient)->getPokemonsByName('picachu');

        $this->assertCount(1, $pokemons);
        $this->assertSame('pikachu', $pokemons[0]->name);
        $this->assertSame(35, $pokemons[0]->hp);
        $this->assertSame('pikachu.png', $pokemons[0]->image);
    }

    public function test_get_pokemons_by_name_returns_multiple_partial_matches(): void
    {
        Redis::shouldReceive('hget')
            ->once()
            ->with('pokeBattle:pokemons:idx', 'pi')
            ->andReturn(null);

        Redis::shouldReceive('hkeys')
            ->once()
            ->with('pokeBattle:pokemons:idx')
            ->andReturn(['pikachu', 'pidgey', 'bulbasaur']);

        Redis::shouldReceive('hget')
            ->once()
            ->with('pokeBattle:pokemons:idx', 'pikachu')
            ->andReturn(json_encode(['name' => 'pikachu']));

        Redis::shouldReceive('hget')
            ->once()
            ->with('pokeBattle:pokemons:idx', 'pidgey')
            ->andReturn(json_encode(['name' => 'pidgey']));

        $pokeApiClient = Mockery::mock(PokeApiClient::class);
        $pokeApiClient->shouldReceive('findPokemon')
            ->once()
            ->with('pikachu')
            ->andReturn($this->pokeApiPokemon('pikachu', 35));
        $pokeApiClient->shouldReceive('findPokemon')
            ->once()
            ->with('pidgey')
            ->andReturn($this->pokeApiPokemon('pidgey', 40));

        $pokemons = new PokemonService($pokeApiClient)->getPokemonsByName('pi');

        $this->assertSame(['pikachu', 'pidgey'], array_map(
            fn ($pokemon): string => $pokemon->name,
            $pokemons
        ));
    }

    public function test_get_pokemons_by_name_returns_empty_array_when_not_found(): void
    {
        Redis::shouldReceive('hget')
            ->once()
            ->with('pokeBattle:pokemons:idx', 'missingno')
            ->andReturn(null);

        Redis::shouldReceive('hkeys')
            ->once()
            ->with('pokeBattle:pokemons:idx')
            ->andReturn([]);

        Redis::shouldReceive('hget')
            ->once()
            ->with('pokeBattle:pokemons:soundex', soundex('missingno'))
            ->andReturn(null);

        $pokeApiClient = Mockery::mock(PokeApiClient::class);
        $pokeApiClient->shouldReceive('findPokemon')
            ->once()
            ->with('missingno')
            ->andThrow(new RuntimeException('Integration request failed with status 404'));

        $pokemons = new PokemonService($pokeApiClient)->getPokemonsByName('missingno');

        $this->assertSame([], $pokemons);
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
