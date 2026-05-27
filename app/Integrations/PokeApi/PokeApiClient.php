<?php

namespace App\Integrations\PokeApi;

use App\Integrations\IntegrationClient;

class PokeApiClient extends IntegrationClient
{
    private const POKEMON_PAGE_SIZE = 100;

    public function __construct()
    {
        $this->baseUrl = config('services.pokeapi.base_url');
    }

    public function findPokemon(string $name): array
    {
        return parent::get("/pokemon/{$name}");
    }

    public function listAllPokemons(): array
    {
        $limit = self::POKEMON_PAGE_SIZE;
        $offset = 0;
        $response = parent::get('/pokemon', [
            'limit' => $limit,
            'offset' => $offset,
        ]);
        $total = $response['count'] ?? 0;
        $results = $response['results'] ?? [];

        $offset += $limit;

        while ($offset < $total) {
            $page = parent::get('/pokemon', [
                'limit' => $limit,
                'offset' => $offset,
            ]);

            $results = array_merge($results, $page['results'] ?? []);
            $offset += $limit;
        }

        $response['results'] = $results;

        return $response;
    }
}
