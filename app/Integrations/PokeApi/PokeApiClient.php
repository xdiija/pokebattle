<?php

namespace App\Integrations\PokeApi;

use App\Exceptions\PokemonNotFoundException;
use App\Integrations\IntegrationClient;
use Illuminate\Support\Facades\Http;

class PokeApiClient extends IntegrationClient
{
    public function __construct()
    {
        $this->baseUrl = config('services.pokeapi.base_url');
    }

    public function findPokemon(string $name): array
    {
        $name = $this->normalizeName($name);

        $response = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->acceptJson()
            ->get("/pokemon/{$name}");

        if ($response->status() === 404) {
            throw new PokemonNotFoundException("Pokemon '{$name}' not found.");
        }

        if ($response->failed()) {
            throw new \RuntimeException('Failed to connect to PokéAPI.');
        }

        return $response->json();
    }

    private function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }
}
