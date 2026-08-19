# eclEZapp — estado vivo do desenvolvimento

> Documento operacional obrigatório para continuidade entre agentes.
>
> **Última atualização:** 19 de agosto de 2026
> **Fase atual:** Fase 1 — Estrutura e acesso
> **Incremento atual:** configuração operacional e validação real do login Google

## 1. Como manter este documento

Ao iniciar um trabalho, mova o incremento correspondente para **Em andamento** e registre escopo e critérios de aceite. Ao concluir:

1. transfira os resultados para **Desenvolvido**;
2. registre testes e comandos de validação executados;
3. atualize **Próximos incrementos**;
4. documente decisões, suposições e bloqueios;
5. mantenha detalhes de implementação nos ADRs ou documentos de módulo, usando este arquivo como índice operacional.

Não marque algo como desenvolvido apenas porque arquivos foram criados. O incremento precisa cumprir a Definition of Done aplicável.

## 2. Desenvolvido

### Fundação do monorepo

- monorepo pnpm com `apps/api`, `apps/web`, `apps/mobile` e pacotes compartilhados;
- Laravel 13 com PHP 8.4, React/Vite e Expo SDK 57 inicializados;
- Docker Compose com API, Nginx, web, PostgreSQL 17, Redis, fila, agendador e Mailpit;
- imagens de desenvolvimento PHP e Node com versões fixadas;
- endpoint versionado `GET /api/v1/health` e contrato OpenAPI inicial;
- CI inicial para PHP, TypeScript e OpenAPI;
- Pest, Pint e Larastan configurados;
- documentação-base e ADR do monólito modular;
- ambiente local validado em 19 de agosto de 2026.

### Estrutura e identidade — fundação PostgreSQL

- extensões `pgcrypto`, `citext` e `btree_gist` habilitadas por migration;
- tabelas e modelos modulares de dioceses, paróquias, comunidades e locais;
- tabelas e modelos modulares de pessoas, usuários, vínculos paroquiais, catálogo de papéis e concessões;
- UUIDs, `timestamptz`, índices, unicidades, checks de estado e exclusões restritivas;
- chave estrangeira composta impede vincular local a comunidade de outra paróquia;
- e-mail de login usa `citext` e unicidade sem diferença entre maiúsculas e minúsculas;
- pessoa permanece independente de usuário; o futuro servo continuará sendo outro vínculo;
- serviço `postgres_test` efêmero e proteção que recusa testes contra banco sem sufixo `_test`;
- migrations validadas em criação, rollback completo e reaplicação.

### Autenticação do MVP

- Laravel Sanctum configurado para autenticação web stateful por cookie e CSRF;
- login local e logout sem cadastro público, com limitação de tentativas e mensagens que não revelam a existência da conta;
- endpoint protegido `GET /api/v1/me`, limitado à pessoa e aos vínculos paroquiais ativos do usuário;
- login com Google implementado com Socialite/OpenID Connect;
- conta Google somente é vinculada a um usuário ativo previamente convidado, após confirmação de e-mail verificado;
- o identificador estável do provedor é persistido em `user_external_identities`; tokens de acesso e atualização do Google não são armazenados;
- tela web de acesso local e Google, responsiva e validada no navegador sem erros de console;
- contrato OpenAPI e exemplos de ambiente atualizados.

### Validações já comprovadas

- API respondeu `200` em `http://localhost:8080/api/v1/health`;
- web respondeu `200` em `http://localhost:3000`;
- Mailpit respondeu `200` em `http://localhost:8025`;
- testes backend: 13 testes e 33 asserções aprovados em PostgreSQL real;
- Pint sem erros;
- PHPStan nível 8 sem erros;
- typecheck web/mobile sem erros;
- lint web/mobile sem erros;
- build de produção da web concluído.

## 3. Em andamento

### Configuração operacional do login Google

Escopo:

- criar um cliente OAuth 2.0 do tipo aplicação Web no Google Cloud;
- autorizar a origem JavaScript `http://localhost:3000`;
- autorizar o retorno `http://localhost:8080/auth/google/callback`;
- preencher `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET` somente no `.env` local ou no cofre do ambiente;
- executar o fluxo real com uma conta Gmail previamente convidada.

Critérios de aceite:

- o Google retorna ao callback configurado;
- uma conta Gmail convidada e ativa entra no sistema;
- uma conta desconhecida, bloqueada ou com e-mail não verificado não cria usuário e não autentica;
- nenhum segredo ou token OAuth é versionado;
- o teste real é registrado neste documento.

## 4. Próximos incrementos

1. configurar e validar as credenciais reais do Google;
2. Policies e resolução segura da paróquia ativa;
3. convites de usuário sem cadastro público;
4. CRUD protegido de dioceses, paróquias, comunidades e locais;
5. organização pastoral: áreas, funções e coordenações com vigência;
6. cadastro vertical de servo sem usuário;
7. templates versionados e criação de evento por snapshot;
8. agenda mensal e publicação;
9. escala individual transacional com proteção GiST;
10. painel web e, depois, fluxo mobile.

## 5. Decisões e suposições vigentes

- o banco e a API permitem que uma mesma pessoa/conta tenha vínculos com mais de uma paróquia; nenhuma interface obrigará esse uso até decisão do produto;
- não haverá cadastro público no MVP;
- o MVP terá login local e login Google; o Google é uma alternativa de autenticação, não um fluxo de autocadastro;
- o primeiro acesso Google exige usuário ativo previamente convidado e e-mail verificado; depois do vínculo, o identificador estável do provedor prevalece;
- tokens OAuth do Google não serão persistidos enquanto o produto não precisar acessar APIs Google em nome do usuário;
- `COORDINATOR` não será papel global: coordenação terá área e vigência próprias;
- templates, equipes e eventos preservarão snapshots históricos;
- operações de escala dependerão de PostgreSQL real e proteção concorrente;
- questões abertas dos documentos-base permanecem abertas quando não forem necessárias ao incremento atual.

## 6. Bloqueios e decisões de produto pendentes

Consulte as seções de decisões pendentes em:

- `docs/ECLEZAPP_AGENTS_AND_DATABASE.md`;
- `docs/ECLEZAPP_DEVELOPMENT_GUIDE.md`.

Antes de implementar confirmação de servo, equipes de música, publicação em múltiplos estágios, coordenação comunitária ou exposição de contatos, obtenha decisão explícita do responsável pelo produto.

## 7. Histórico de incrementos

| Data | Incremento | Resultado |
|---|---|---|
| 19/08/2026 | Reinício da fundação | Repositório recriado a partir dos novos documentos; Docker e verificações-base aprovados |
| 19/08/2026 | Estrutura e identidade | Migrations, modelos e constraints iniciais aprovados em PostgreSQL real; suíte protegida contra uso do banco de desenvolvimento |
| 19/08/2026 | Autenticação do MVP | Login local, sessão Sanctum, `/api/v1/me` e login Google por convite implementados; 13 testes e 33 asserções aprovados; validação real do Google depende das credenciais OAuth |
