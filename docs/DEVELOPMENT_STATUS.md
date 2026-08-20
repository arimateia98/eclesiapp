# eclEZapp — estado vivo do desenvolvimento

> Documento operacional obrigatório para continuidade entre agentes.
>
> **Última atualização:** 19 de agosto de 2026
> **Fase atual:** Fase 1 — Estrutura e acesso
> **Incremento atual:** coordenações pastorais e gestão de acessos

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
- autocadastro local e logout com sessão segura, limitação de tentativas e senha confirmada;
- endpoint protegido `GET /api/v1/me`, limitado à pessoa e aos vínculos paroquiais ativos do usuário;
- login com Google implementado com Socialite/OpenID Connect;
- primeiro acesso Google cria ou vincula uma conta a partir de e-mail verificado, sem conceder paróquia ou condição de servo;
- o identificador estável do provedor é persistido em `user_external_identities`; tokens de acesso e atualização do Google não são armazenados;
- tela web de acesso local e Google, responsiva e validada no navegador sem erros de console;
- preflight CORS cobre login, autocadastro, logout, CSRF e rotas versionadas usadas pelo painel web;
- contrato OpenAPI e exemplos de ambiente atualizados.

### Contas sem paróquia

- usuário pode se registrar antes de possuir vínculo paroquial;
- cadastro cria `people + users`, mas não cria membership, papel ou `servants`;
- conta sem paróquia autentica e consulta o próprio perfil, recebendo lista paroquial vazia;
- recursos paroquiais retornam `NO_ACTIVE_PARISH_MEMBERSHIP` para essa conta;
- interface explica que conta, vínculo paroquial e servo são condições independentes;
- decisão registrada no ADR 0004.

### Servos — vínculo pastoral inicial

- tabela `servants` separada de `people` e `users`, com UUID, paróquia, estado, período e autor do cadastro;
- restrições de estado, período, unicidade por pessoa/paróquia, foreign keys e índice parcial para vínculos ativos;
- padre ou administrador paroquial vigente pode listar, pesquisar, cadastrar e alterar o estado de servos;
- criar servo cria uma pessoa sem gerar usuário ou credenciais;
- usuário `PARISH_VIEWER` não administra servos;
- divergência entre paróquia da rota e contexto autorizado é bloqueada antes da consulta;
- inativação e suspensão preservam o histórico do vínculo;
- catálogo inicial de papéis paroquiais é idempotente no seeder;
- endpoints e schema de servo documentados no OpenAPI.

### Catálogo pastoral, habilitações e painel administrativo

- tabelas `pastoral_areas`, `pastoral_functions` e `servant_functions` criadas por migration reversível;
- chaves compostas no PostgreSQL impedem habilitar um servo em função de outra paróquia;
- estados, modos de designação, períodos, unicidades e autoria protegidos no banco;
- padre ou administrador pode listar e criar áreas e funções e habilitar servos ativos;
- habilitação pode existir para servo sem conta de usuário e registra quem a aprovou;
- Policy compartilhada centraliza a regra de administração pastoral sem conceder acesso a `PARISH_VIEWER`;
- painel autenticado apresenta conta sem paróquia, contexto paroquial, estados vazios/erros e cadastros de servos, áreas, funções e habilitações;
- dados de contato continuam restritos aos endpoints de padre ou administrador;
- endpoints e schemas foram incorporados ao OpenAPI.

### Contexto paroquial e autorização base

- Policy de paróquia exige simultaneamente paróquia ativa e vínculo paroquial ativo;
- seleção de paróquia ativa persistida por sessão web, sem confiar no identificador enviado pela interface;
- resolução por `X-Parish-Id` disponível para clientes sem estado, incluindo o futuro aplicativo mobile;
- único vínculo ativo é resolvido automaticamente; múltiplos vínculos exigem seleção explícita;
- seleção inexistente, externa, suspensa ou encerrada retorna o mesmo erro sem permitir enumeração;
- contexto retorna somente papéis vigentes na paróquia e na data da requisição;
- middleware `active.parish` criado como fronteira obrigatória para os próximos recursos paroquiais;
- endpoints de seleção, remoção e consulta documentados no OpenAPI;
- decisão registrada no ADR 0003.

### Validações já comprovadas

- API respondeu `200` em `http://localhost:8080/api/v1/health`;
- web respondeu `200` em `http://localhost:3000`;
- Mailpit respondeu `200` em `http://localhost:8025`;
- testes backend: 33 testes e 120 asserções aprovados em PostgreSQL real;
- Pint sem erros;
- PHPStan nível 8 sem erros;
- typecheck web/mobile sem erros;
- lint web/mobile sem erros;
- build de produção da web concluído.

## 3. Em andamento

### Coordenações pastorais e gestão de acessos

Escopo:

- criar atribuição de coordenação por área e período de vigência;
- permitir que padre ou administrador vincule uma conta existente como coordenador;
- liberar ao coordenador somente os servos e funções das áreas sob sua responsabilidade;
- manter coordenador como usuário sem criar `servants` automaticamente.

Critérios de aceite:

- atribuição possui início, fim opcional, autor e histórico preservado;
- coordenador precisa de conta e membership ativo, mas não precisa ser servo;
- permissão é limitada à área coordenada e ao período vigente;
- isolamento entre paróquias e áreas possui testes de Policy e PostgreSQL;
- painel diferencia claramente padre, administrador e coordenador.

## 4. Próximos incrementos

1. coordenações por área com vigência e permissões limitadas;
2. gestão de usuários administrativos para padres e coordenadores;
3. CRUD mínimo de comunidades e locais necessário aos eventos;
4. templates versionados e criação de evento por snapshot;
5. agenda mensal e publicação;
6. escala individual transacional apontando para `servants`;
7. fluxo mobile do servo somente quando a pessoa também possuir usuário;
8. configurar verificação de e-mail e recuperação de senha;
9. configurar e validar as credenciais reais do Google quando o cliente OAuth estiver disponível.

## 5. Decisões e suposições vigentes

- o banco e a API permitem que uma mesma pessoa/conta tenha vínculos com mais de uma paróquia; nenhuma interface obrigará esse uso até decisão do produto;
- haverá autocadastro no MVP sem concessão automática de paróquia, papel ou condição de servo;
- o MVP terá cadastro e login local e Google; o primeiro acesso Google exige e-mail verificado;
- `users` representa credencial; `servants` representa vínculo escalável e nenhum deles substitui `people`;
- padres e coordenadores precisam de usuário, mas só terão servo se também puderem ser escalados;
- tokens OAuth do Google não serão persistidos enquanto o produto não precisar acessar APIs Google em nome do usuário;
- contexto paroquial é específico da sessão ou requisição e nunca será uma propriedade global persistida no usuário;
- toda rota com dados paroquiais deverá usar `active.parish` e ainda aplicar a Policy específica da ação;
- `COORDINATOR` não será papel global: coordenação terá área e vigência próprias;
- templates, equipes e eventos preservarão snapshots históricos;
- operações de escala dependerão de PostgreSQL real e proteção concorrente;
- questões abertas dos documentos-base permanecem abertas quando não forem necessárias ao incremento atual.

## 6. Bloqueios e decisões de produto pendentes

- a validação ponta a ponta do login Google depende da criação externa do cliente OAuth e do preenchimento seguro de `GOOGLE_CLIENT_ID` e `GOOGLE_CLIENT_SECRET`;

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
| 19/08/2026 | Contexto paroquial | Policy, seleção por sessão, cabeçalho para cliente sem estado, papéis vigentes e middleware de isolamento implementados; 19 testes e 49 asserções aprovados |
| 19/08/2026 | Autocadastro desacoplado | Cadastro local e Google permitido sem paróquia, papel ou servo; ADR 0004 supera a exigência anterior de convite para criar conta |
| 19/08/2026 | Servos — núcleo | Tabela, modelo, Policies e API inicial de servos sem usuário; isolamento por paróquia e histórico de estado testados; 28 testes e 95 asserções aprovados |
| 19/08/2026 | Catálogo e painel pastoral | Áreas, funções e habilitações com integridade composta; painel para padre/administrador e fluxo de servo sem usuário; 32 testes e 113 asserções aprovados |
| 19/08/2026 | Correção do autocadastro web | Rota `/register` incluída no CORS com teste de preflight para o painel em `localhost:3000` |
