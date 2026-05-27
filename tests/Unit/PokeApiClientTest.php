<?php

namespace Tests\Unit;

use App\Integrations\PokeApi\PokeApiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PokeApiClientTest extends TestCase
{
    public function test_list_pokemons_gets_all_pages(): void
    {
        config(['services.pokeapi.base_url' => 'https://pokeapi.test']);

        Http::fake([
            'pokeapi.test/pokemon?limit=100&offset=0' => Http::response([
                'count' => 101,
                'results' => [
                    ['name' => 'bulbasaur'],
                ],
            ]),
            'pokeapi.test/pokemon?limit=100&offset=100' => Http::response([
                'count' => 101,
                'results' => [
                    ['name' => 'mew'],
                ],
            ]),
        ]);

        $pokemons = new PokeApiClient()->listAllPokemons();

        $this->assertSame(101, $pokemons['count']);
        $this->assertSame([
            ['name' => 'bulbasaur'],
            ['name' => 'mew'],
        ], $pokemons['results']);
    }
}
