# Poke Battle Backend

API em Laravel para simular uma batalha entre dois Pokemons usando dados da PokeAPI.

A regra da batalha e simples: vence o Pokemon com maior HP. Em caso de HP igual, o resultado indica empate.

## Tecnologias

- PHP com Laravel: estrutura simples para API, validacao e testes.
- PokeAPI: fonte dos dados dos Pokemons.
- Redis: cache dos nomes dos Pokemons e indice Soundex para busca aproximada.
- PHPUnit: testes automatizados.

## Como rodar

Clone o projeto e acesse a pasta:

```sh
cd pokeBattle
```

Crie o arquivo `.env`:

```sh
cp .env.example .env
```

Suba os containers:

```sh
docker-compose up -d
```

Acesse o container da aplicacao:

```sh
docker-compose exec app bash
```

Instale as dependencias:

```sh
composer install
```

Gere a chave da aplicacao:

```sh
php artisan key:generate
```

A API ficara disponivel em:

```txt
http://localhost:8989
```

## Endpoints

Criar/atualizar o cache de Pokemons:

```http
GET /api/pokemons/cache
```

Buscar Pokemons por nome:

```http
GET /api/pokemons/{name}
```

Executar uma batalha:

```http
POST /api/pokemons/battle
```

Body:

```json
{
  "pokemon_one": "pikachu",
  "pokemon_two": "charizard"
}
```

## Testes

Dentro do container:

```sh
php artisan test
```

## Decisoes tecnicas

- `PokeApiClient` concentra as chamadas HTTP para a PokeAPI.
- `PokemonService` concentra regras de busca, cache, normalizacao de nomes e tratamento de Pokemon nao encontrado.
- `PokemonDTO` reduz a resposta da PokeAPI para os dados usados pela aplicacao: `name`, `hp` e `image`.
- `PokemonBattleRequest` valida a entrada da batalha antes de chegar ao controller.
- O cache em Redis guarda um indice por nome e um indice Soundex para permitir busca aproximada.
