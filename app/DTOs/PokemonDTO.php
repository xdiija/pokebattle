<?php

namespace App\DTOs;

use JsonSerializable;
use RuntimeException;

readonly class PokemonDTO implements JsonSerializable
{
    public function __construct(
        public string $name,
        public int $hp,
        public ?string $image,
    ) {}

    public static function fromPokeApiResponse(array $pokemon): self
    {
        return new self(
            name: $pokemon['name'],
            hp: self::extractHp($pokemon),
            image: $pokemon['sprites']['other']['official-artwork']['front_default']
                ?? $pokemon['sprites']['front_default']
                ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'hp' => $this->hp,
            'image' => $this->image,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function extractHp(array $pokemon): int
    {
        foreach ($pokemon['stats'] as $stat) {
            if ($stat['stat']['name'] === 'hp') {
                return $stat['base_stat'];
            }
        }

        throw new RuntimeException('HP stat not found.');
    }
}
