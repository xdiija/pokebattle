# POKE BATTLE BACKEND

## Steps to 1st build
Após clonar o repositório, acesse a pasta do projeto pelo terminal.

Crie o Arquivo .env
```sh
cp .env.example .env
``````

Suba os containers do projeto
```sh
docker-compose up -d
```

Acesse o container app
```sh
docker-compose exec app bash
```

Instale as dependências do projeto
```sh
composer install
```

Gere a key do projeto Laravel
```sh
php artisan key:generate
```
Acesse o projeto
[http://localhost:8989](http://localhost:8989)
