<?php

namespace App\Services;

use App\DTOs\PokemonDTO;
use App\Exceptions\PokemonNotFoundException;
use App\Integrations\PokeApi\PokeApiClient;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use RuntimeException;

class PokemonService
{
    private const POKEMON_CACHE_PREFIX = 'pokeBattle:pokemons';

    public function __construct(
        private readonly PokeApiClient $pokeApiClient
    ) {}

    public function getPokemonByName(string $name): PokemonDTO
    {
        $name = $this->normalizeName($name);

        try {
            return PokemonDTO::fromPokeApiResponse(
                $this->pokeApiClient->findPokemon($name)
            );
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'status 404')) {
                throw new PokemonNotFoundException("Pokemon '{$name}' not found.", previous: $exception);
            }

            throw new RuntimeException('Failed to connect to PokéAPI.', previous: $exception);
        }
    }

    public function getPokemonsByName(string $name): array
    {
        $normalizedName = $this->normalizeName($name);
        $cachedPokemon = Redis::hget($this->pokemonIndexKey(), $normalizedName);

        if ($cachedPokemon) {
            $pokemon = json_decode($cachedPokemon, true);

            return $this->findPokemons([$pokemon]);
        }

        $matchingCachedPokemons = $this->getMatchingCachedPokemons($normalizedName);

        if ($matchingCachedPokemons !== []) {
            return $this->findPokemons($matchingCachedPokemons);
        }

        $soundexMatches = $this->searchSoundexIndex($normalizedName);
        $soundexPokemons = [];

        foreach ($soundexMatches as $field) {
            $cachedPokemon = Redis::hget($this->pokemonIndexKey(), $field);

            if ($cachedPokemon) {
                $soundexPokemons[] = json_decode($cachedPokemon, true);
            }
        }

        if ($soundexPokemons !== []) {
            return $this->findPokemons($soundexPokemons);
        }

        try {
            return [$this->getPokemonByName($normalizedName)];
        } catch (PokemonNotFoundException) {
            return [];
        }
    }

    public function cachePokemons(): array
    {
        try {
            $indexKey = $this->pokemonIndexKey();
            $soundexKey = $this->pokemonSoundexKey();
            $tmpIndexKey = "{$indexKey}:tmp";
            $tmpSoundexKey = "{$soundexKey}:tmp";

            $pokemons = $this->pokeApiClient->listAllPokemons()['results'] ?? [];

            Redis::del($tmpIndexKey, $tmpSoundexKey);

            foreach ($pokemons as $pokemon) {
                $pokemonEncoded = json_encode($pokemon);
                $normalizedName = $this->normalizeName($pokemon['name']);
                Redis::hset($tmpIndexKey, $normalizedName, $pokemonEncoded);
            }

            $this->buildSoundexIndex($tmpIndexKey, $tmpSoundexKey);

            Redis::rename($tmpIndexKey, $indexKey);
            Redis::rename($tmpSoundexKey, $soundexKey);

            return ['message' => 'Pokemons cached successfully.'];
            
        } catch (\Throwable) {
            return ['message' => 'Failed to cache pokemons.'];
        }
    }

    public function getMatchingCachedPokemons(string $name): array
    {
        $fields = Redis::hkeys($this->pokemonIndexKey());
        $matchingPokemons = [];
        $normalizedName = $this->normalizeName($name);

        foreach ($fields as $field) {
            $normalizedField = $this->normalizeName($field);

            if (str_contains($normalizedField, $normalizedName)) {
                $cachedPokemon = Redis::hget($this->pokemonIndexKey(), $field);

                if ($cachedPokemon) {
                    $matchingPokemons[] = json_decode($cachedPokemon, true);
                }
            }
        }

        return $matchingPokemons;
    }

    private function findPokemons(array $cachedPokemons): array
    {
        $pokemons = [];
        $searchedNames = [];

        foreach ($cachedPokemons as $cachedPokemon) {
            $name = $this->normalizeName($cachedPokemon['name']);

            if (in_array($name, $searchedNames, true)) {
                continue;
            }

            $searchedNames[] = $name;

            try {
                $pokemons[] = $this->getPokemonByName($name);
            } catch (PokemonNotFoundException) {
                continue;
            }
        }

        return $pokemons;
    }

    public function searchSoundexIndex(string $name): array
    {
        $results = [];

        foreach ($this->generateSoundexWords($name) as $soundexCode) {
            $cachedNames = Redis::hget($this->pokemonSoundexKey(), $soundexCode);

            if (! $cachedNames) {
                continue;
            }

            $results = array_merge($results, json_decode($cachedNames, true));
        }

        return array_values(array_unique($results));
    }

    public function buildSoundexIndex(?string $indexKey = null, ?string $soundexKey = null): void
    {
        $soundexIndex = [];
        $indexKey ??= $this->pokemonIndexKey();
        $soundexKey ??= $this->pokemonSoundexKey();
        $names = Redis::hkeys($indexKey);

        foreach ($names as $name) {
            foreach ($this->generateSoundexWords($name) as $soundexCode) {
                $soundexIndex[$soundexCode][] = $name;
            }
        }

        Redis::del($soundexKey);

        foreach ($soundexIndex as $soundexCode => $names) {
            Redis::hset(
                $soundexKey,
                $soundexCode,
                json_encode(array_values(array_unique($names)))
            );
        }
    }

    private function normalizeName(string $name): string
    {
        return Str::of($name)
            ->ascii()
            ->lower()
            ->trim()
            ->replaceMatches('/[\s_]+/', '-')
            ->replaceMatches('/-+/', '-')
            ->trim('-')
            ->toString();
    }

    private function generateSoundexWords(string $text): array
    {
        $words = preg_split('/[\s,\-]+/', $text, flags: PREG_SPLIT_NO_EMPTY);
        return array_map('soundex', $words ?: []);
    }

    private function pokemonIndexKey(): string
    {
        return self::POKEMON_CACHE_PREFIX.':idx';
    }

    private function pokemonSoundexKey(): string
    {
        return self::POKEMON_CACHE_PREFIX.':soundex';
    }
}
