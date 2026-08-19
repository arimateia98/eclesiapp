# eclEZapp

Plataforma de gestão pastoral para estrutura paroquial, agendas, equipes e escalas de serviço.

## Estado

O projeto foi reiniciado em 19 de agosto de 2026 com base nos documentos de arquitetura versão 0.1. A fundação usa um monorepo com Laravel 13, React, Expo, PostgreSQL e Redis.

## Estrutura

- `apps/api`: API Laravel 13;
- `apps/web`: aplicação administrativa React/Vite;
- `apps/mobile`: aplicativo React Native/Expo;
- `packages`: contratos e configurações compartilhados;
- `docs`: domínio, arquitetura, OpenAPI e ADRs;
- `infra`: imagens e configuração local.

## Pré-requisitos

- Docker Desktop em execução;
- Node.js 22.13 ou superior;
- pnpm 11.19;
- `make` opcional para os atalhos documentados.

## Início rápido

1. Copie `.env.example` para `.env`.
2. Execute `make setup`.
3. Execute `make up`.
4. Acesse a web em `http://localhost:3000`, a API em `http://localhost:8080` e o Mailpit em `http://localhost:8025`.

O aplicativo mobile é executado no host com `make mobile`.

## Login com Google

O MVP aceita cadastro e login local ou Google. Criar conta não concede vínculo paroquial, papel administrativo ou condição de servo. Para habilitar o fluxo Google:

1. crie no Google Cloud um cliente OAuth 2.0 do tipo aplicação Web;
2. autorize a origem `http://localhost:3000`;
3. autorize o retorno `http://localhost:8080/auth/google/callback`;
4. preencha `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` no `.env`, sem versionar o segredo.

Sem essas credenciais, o botão permanece visível, mas a API responde com indisponibilidade controlada em vez de iniciar o fluxo externo.

## Testes

Execute `make test`. Esse comando usa o serviço efêmero `postgres_test` e um banco cujo nome termina em `_test`. A suíte interrompe a execução se detectar outro banco, protegendo os dados locais de desenvolvimento.

## Documentação obrigatória

- `docs/ECLEZAPP_AGENTS_AND_DATABASE.md`;
- `docs/ECLEZAPP_DEVELOPMENT_GUIDE.md`;
- `docs/DEVELOPMENT_STATUS.md`;
- `docs/adr/`.
