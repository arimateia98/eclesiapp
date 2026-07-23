# Eclesiapp

Plataforma de gestão pastoral para organizações religiosas, começando por escalas internas com prevenção de conflitos.

## Stack

- PHP 8.4, Laravel 13 e Sanctum;
- PostgreSQL 17 e Redis 7;
- Vue 3, TypeScript 6 e Vite 8;
- Docker Compose, Nginx, Queue, Scheduler e Mailpit.

O aplicativo mobile está reservado para uma etapa posterior ao primeiro MVP.

## Requisitos

- Docker Desktop com Docker Compose v2;
- Git;
- portas locais `5173`, `8080`, `5433`, `6380`, `8025` e `1025` disponíveis.

PHP, Composer, Node e npm locais não são necessários.

## Primeiro uso

Crie os arquivos locais de ambiente a partir dos exemplos:

```powershell
Copy-Item .env.example .env
Copy-Item backend/.env.example backend/.env
Copy-Item frontend/.env.example frontend/.env
```

Construa a imagem, gere a chave local, suba os serviços e execute as migrations:

```powershell
docker compose build
docker compose run --rm app php artisan key:generate
docker compose up -d
docker compose exec app php artisan migrate --force
```

Endereços locais:

- painel: <http://localhost:5173>;
- API: <http://localhost:8080/api/v1/health>;
- Mailpit: <http://localhost:8025>.

As portas do banco e do Redis são publicadas apenas em `127.0.0.1`. Os valores dos arquivos de exemplo são exclusivos para desenvolvimento e devem ser substituídos fora do ambiente local.

## Qualidade

```powershell
docker compose run --rm app composer lint
docker compose run --rm app composer analyse
docker compose run --rm app composer test
docker compose run --rm frontend npm run lint
docker compose run --rm frontend npm run test
docker compose run --rm frontend npm run build
```

Para iniciar as ferramentas opcionais:

```powershell
docker compose --profile tools up -d adminer redisinsight
```

O comando `docker compose down` para os containers sem remover volumes ou dados.

## Organização do repositório

```text
backend/   API Laravel e módulos do domínio
frontend/  painel administrativo Vue
mobile/    aplicativo futuro dos servos
docker/    imagens e configurações locais
docs/      arquitetura, escopo e decisões
```

Antes de implementar regras do domínio, leia [AGENTS.md](AGENTS.md), [visão de arquitetura](docs/architecture/overview.md) e [ADRs](docs/decisions/README.md).
