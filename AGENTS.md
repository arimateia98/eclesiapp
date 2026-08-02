# AGENTS.md — Eclesiapp

## 1. Papel esperado do Codex

Atue como engenheiro de software sênior, você é um desenvolvedor sênior com mais de 20 anos de experiência. Especialista em arquitetura de software, código limpo, segurança, performance, DevOps com foco em:

- arquitetura sustentável;
- modelagem de domínio;
- integridade de dados;
- segurança;
- escalabilidade sem complexidade prematura;
- testes automatizados;
- observabilidade;
- clareza de código;
- baixo acoplamento e alta coesão;
- documentação das decisões;
- prevenção de dívida técnica.

Antes de alterar qualquer arquivo:

1. Leia este documento integralmente.
2. Leia os documentos em `docs/architecture/`.
3. Leia os ADRs em `docs/decisions/`.
4. Analise a estrutura atual do repositório.
5. Analise o `compose.yaml`, Dockerfiles e variáveis de ambiente.
6. Verifique migrations, models, services, policies e testes existentes.
7. Apresente um plano curto antes de mudanças extensas.
8. Preserve as decisões arquiteturais deste documento.

Não faça alterações destrutivas sem autorização explícita.

---

## 2. Visão do produto

O Eclesiapp é uma plataforma de gestão pastoral para igrejas, paróquias, comunidades, ministérios, movimentos e grupos religiosos.

O problema inicial é organizar escalas litúrgicas e evitar conflitos quando uma mesma pessoa serve em mais de uma pastoral, ministério, comunidade ou missão.

O sistema deve evoluir para permitir:

- cadastro de paróquias, comunidades, capelas, ministérios e movimentos;
- publicação de eventos e missões;
- missões públicas, privadas, restritas ou não listadas;
- convites diretos para ministérios ou pessoas;
- candidatura de ministérios a missões públicas;
- escalas por função;
- prevenção de conflitos de horário e de múltiplas missões no mesmo dia;
- indisponibilidades;
- trocas de escala;
- notificações;
- hinário mensal;
- repertório por celebração;
- atuação de ministérios independentes em várias comunidades;
- expansão para outras pastorais além da Liturgia.

---

## 3. Stack oficial

### Backend

- PHP 8.4;
- Laravel;
- Laravel Sanctum;
- PostgreSQL;
- Redis;
- Laravel Queue;
- Laravel Scheduler.

### Frontend administrativo

- Vue 3;
- TypeScript;
- Vite.

### Mobile

- React Native;
- Expo ou React Native CLI;
- Firebase Cloud Messaging para notificações push.

### Infraestrutura

- Docker;
- Docker Compose;
- Nginx;
- Mailpit;
- Adminer opcional;
- RedisInsight opcional.

---

## 4. Estrutura do repositório

O projeto deve permanecer em monorepositório.

```text
eclesiapp/
├── backend/
├── frontend/
├── mobile/
├── docker/
├── docs/
├── scripts/
├── .github/
├── compose.yaml
├── .env.example
├── .gitignore
├── Makefile
├── README.md
└── AGENTS.md
```

Responsabilidades:

```text
backend/   API Laravel
frontend/  Painel web administrativo
mobile/    Aplicativo dos servos
docker/    Infraestrutura dos containers
docs/      Arquitetura, regras e ADRs
scripts/   Automação de ambiente e manutenção
.github/   CI, templates e automações do repositório
```

---

## 5. Arquitetura principal

O backend deve seguir um monólito modular.

Estrutura sugerida:

```text
backend/app/
├── Modules/
│   ├── Identity/
│   ├── Organizations/
│   ├── Ministries/
│   ├── Scheduling/
│   ├── Missions/
│   ├── Music/
│   └── Notifications/
└── Shared/
```

Estrutura interna esperada por módulo:

```text
Module/
├── Domain/
│   ├── Models/
│   ├── Enums/
│   ├── ValueObjects/
│   ├── Services/
│   ├── Policies/
│   └── Exceptions/
├── Application/
│   ├── Actions/
│   ├── DTOs/
│   ├── Queries/
│   └── Commands/
├── Infrastructure/
│   ├── Persistence/
│   ├── Repositories/
│   ├── Jobs/
│   └── Integrations/
└── Http/
    ├── Controllers/
    ├── Requests/
    └── Resources/
```

Regras:

- controllers devem ser finos;
- regras de negócio não devem ficar em controllers;
- models não devem concentrar toda a lógica do domínio;
- validações de domínio devem existir em services, actions ou policies;
- queries complexas devem ser isoladas;
- integrações externas devem ficar em `Infrastructure`;
- código compartilhado só deve ir para `Shared` quando for realmente transversal.

---

## 6. Princípios obrigatórios do domínio

### 6.1 Pessoa é diferente de usuário

`people` e `users` devem permanecer separados.

Uma pessoa pode:

- ser cadastrada por um coordenador;
- participar de ministérios;
- ser escalada;
- ainda não possuir conta.

A conta será vinculada posteriormente.

Nunca fundir pessoa e usuário em uma única entidade.

### 6.2 Organização é o conceito central

O sistema não deve assumir que toda entidade pertence diretamente a uma paróquia.

Uma organização pode ser:

- diocese;
- paróquia;
- comunidade;
- capela;
- ministério;
- movimento;
- grupo pastoral;
- organização independente.

Estrutura conceitual:

```text
organizations
- id
- name
- slug
- type
- parent_organization_id
- status
- visibility
- created_by
- timestamps
```

Tipos esperados:

```text
diocese
parish
community
chapel
ministry
movement
group
```

Uma organização pode ter pai hierárquico, mas relações adicionais devem ser flexíveis.

### 6.3 Relações entre organizações

Não depender apenas de uma hierarquia rígida.

```text
organization_relationships
- id
- source_organization_id
- target_organization_id
- relationship_type
- status
- started_at
- ended_at
- timestamps
```

Tipos possíveis:

```text
belongs_to
partner_of
serves_at
authorized_by
linked_to
```

Exemplos:

```text
Comunidade São José belongs_to Paróquia São João Batista
Ministério Anseio por Ti serves_at Comunidade São José
```

### 6.4 Ministério pode ser interno ou independente

Um ministério pode:

- pertencer a uma paróquia;
- pertencer a uma comunidade;
- existir como organização independente;
- servir em várias comunidades;
- receber convites;
- candidatar-se a missões;
- possuir calendário próprio;
- escalar seus próprios membros.

Não duplicar o mesmo ministério em cada comunidade onde ele serve.

### 6.5 Missa é um tipo de evento

O sistema deve trabalhar com eventos genéricos.

Exemplos:

- missa;
- celebração;
- adoração;
- procissão;
- formação;
- retiro;
- reunião;
- encontro;
- festa;
- missão pastoral.

```text
events
- id
- publisher_organization_id
- host_organization_id
- event_type_id
- location_id
- title
- description
- starts_at
- ends_at
- visibility
- status
- created_by
- timestamps
```

`publisher_organization_id` é quem publicou.

`host_organization_id` é quem recebe ou sedia.

### 6.6 Missão é uma oportunidade de serviço

Um evento pode gerar uma ou mais missões.

Exemplo:

```text
Evento:
Missa de Nossa Senhora

Missões:
- música;
- leitores;
- salmista;
- ministros da comunhão;
- acolhida.
```

```text
missions
- id
- event_id
- publisher_organization_id
- target_organization_id
- ministry_type_id
- title
- description
- visibility
- participation_policy
- status
- response_deadline
- created_by
- timestamps
```

---

## 7. Visibilidade das missões

Valores esperados:

```text
public
restricted
private
unlisted
```

- **Pública:** pode ser encontrada por organizações ou pessoas elegíveis.
- **Restrita:** visível apenas para organizações autorizadas ou relacionadas.
- **Privada:** criada para organização ou pessoa específica.
- **Não listada:** acessível por convite ou link, sem aparecer em listagens públicas.

A visibilidade de uma missão nunca torna públicos dados pessoais, indisponibilidades ou informações internas.

---

## 8. Política de participação

Valores esperados:

```text
invitation_only
application_required
automatic_acceptance
coordinator_assignment
```

- **Convite direto:** uma organização ou pessoa é convidada.
- **Candidatura:** uma organização demonstra interesse e aguarda aprovação.
- **Aceitação automática:** permitida somente em cenários explicitamente configurados.
- **Escala por coordenador:** usada em fluxos internos tradicionais.

---

## 9. Convites e candidaturas

### Candidaturas

```text
mission_applications
- id
- mission_id
- applicant_organization_id
- applied_by
- message
- status
- reviewed_by
- reviewed_at
- timestamps
```

Status:

```text
pending
accepted
rejected
withdrawn
```

### Convites

```text
mission_invitations
- id
- mission_id
- invited_organization_id
- invited_person_id
- status
- expires_at
- responded_by
- responded_at
- timestamps
```

Status:

```text
pending
accepted
declined
expired
cancelled
```

---

## 10. Participantes de uma missão

Depois de convite ou candidatura aprovada:

```text
mission_participants
- id
- mission_id
- organization_id
- participation_role
- status
- confirmed_at
- timestamps
```

Depois disso, o coordenador responsável poderá escalar membros.

---

## 11. Vagas individuais e vagas para organizações

Uma missão pode solicitar:

- uma pessoa;
- várias pessoas;
- uma organização inteira;
- um ministério completo;
- uma equipe.

```text
mission_slots
- id
- mission_id
- slot_type
- service_function_id
- required_ministry_type_id
- quantity
- required
- timestamps
```

Tipos:

```text
person
organization
```

Exemplos:

```text
1 leitor
1 salmista
3 ministros da comunhão
1 ministério de música completo
1 equipe de acolhida
```

---

## 12. Membros e papéis em organizações

Uma pessoa pode participar de várias organizações.

```text
organization_memberships
- id
- organization_id
- person_id
- role
- status
- joined_at
- left_at
- timestamps
```

Papéis possíveis:

```text
owner
administrator
coordinator
member
guest
```

Exemplo:

```text
João:
- coordenador de um ministério independente;
- músico de uma paróquia;
- leitor de uma comunidade.
```

O conflito deve ser verificado pela pessoa, independentemente da organização.

---

## 13. Funções pastorais

Evitar tabelas específicas como:

```text
musicos
leitores
salmistas
ministros_comunhao
```

Usar estrutura genérica:

```text
service_functions
- id
- organization_id
- ministry_type_id
- name
- active
- timestamps
```

Exemplos:

- Leitor 1;
- Leitor 2;
- Salmista;
- Ministro da Comunhão;
- Vocalista;
- Violonista;
- Guitarrista;
- Tecladista;
- Acolhida.

```text
person_functions
- person_id
- service_function_id
- timestamps
```

---

## 14. Escalas e designações

```text
assignments
- id
- mission_id
- mission_slot_id
- person_id
- assigned_by
- status
- assigned_at
- confirmed_at
- cancelled_at
- cancellation_reason
- timestamps
```

Status possíveis:

```text
pending
confirmed
declined
cancelled
replaced
```

Nunca permitir alteração silenciosa de uma escala publicada.

Toda alteração relevante deve gerar histórico.

---

## 15. Regras de conflito

### 15.1 Conflito de horário

Uma pessoa não pode estar em dois eventos com horários sobrepostos.

### 15.2 Mais de uma missão no mesmo dia

A organização pode configurar que uma pessoa não deve servir em mais de uma missão no mesmo dia.

Essa regra deve ser configurável por tipo de evento, organização ou política.

### 15.3 Exceções

Exceções só podem ser realizadas por usuário autorizado e com justificativa obrigatória.

### 15.4 Validação

A validação deve acontecer:

- no backend;
- dentro de transação;
- com proteção contra concorrência;
- antes da persistência;
- novamente em trocas ou substituições.

### 15.5 Concorrência

Quando duas coordenações tentarem escalar a mesma pessoa simultaneamente, usar transação e bloqueio de linha.

```php
DB::transaction(function () use ($personId, $missionId) {
    $person = Person::query()
        ->whereKey($personId)
        ->lockForUpdate()
        ->firstOrFail();

    // Validar indisponibilidades.
    // Validar sobreposição.
    // Validar limite diário.
    // Criar designação.
});
```

Criar testes de concorrência quando viável.

---

## 16. Indisponibilidades

```text
unavailabilities
- id
- person_id
- starts_at
- ends_at
- reason_type
- note
- status
- created_at
- updated_at
```

Motivos iniciais:

```text
work
study
transport
other_authorized
```

Os motivos devem ser configuráveis por organização.

Indisponibilidades recorrentes:

```text
availability_rules
- id
- person_id
- weekday
- starts_at_time
- ends_at_time
- valid_from
- valid_until
- reason_type
- timestamps
```

Não expor detalhes sensíveis para usuários sem autorização.

---

## 17. Trocas de escala

O servo não altera diretamente a escala publicada.

Fluxo:

1. solicita troca;
2. informa motivo;
3. indica substituto ou deixa aberta;
4. outro servo aceita;
5. coordenador aprova;
6. conflitos são revalidados;
7. escala é atualizada;
8. histórico é criado;
9. notificações são enviadas.

```text
swap_requests
- id
- assignment_id
- requested_by
- proposed_person_id
- reason
- status
- approved_by
- created_at
- resolved_at
```

Status:

```text
open
accepted
approved
rejected
cancelled
expired
```

---

## 18. Música, hinário e repertório

### Músicas

```text
songs
- id
- organization_id
- title
- artist_or_ministry
- external_link
- notes
- active
- timestamps
```

### Hinário mensal

```text
hymnals
- id
- organization_id
- year
- month
- title
- published_at
- timestamps
```

### Itens do hinário

```text
hymnal_items
- id
- hymnal_id
- song_id
- liturgical_part_id
- celebration_reference
- notes
- position
- timestamps
```

### Repertório por evento

```text
event_repertoires
event_repertoire_items
```

Momentos litúrgicos iniciais:

- Entrada;
- Ato penitencial;
- Glória;
- Salmo;
- Aclamação;
- Ofertório;
- Santo;
- Cordeiro;
- Comunhão;
- Pós-comunhão;
- Final.

Hinário mensal é sugestão.

Repertório do evento é a seleção efetiva.

---

## 19. Notificações

Eventos de notificação:

- nova escala;
- escala alterada;
- convite para missão;
- candidatura recebida;
- candidatura aceita ou rejeitada;
- troca solicitada;
- troca aceita;
- troca recusada;
- lembrete de missão;
- novo hinário;
- mudança de horário;
- cancelamento de evento.

Usar filas.

```text
notification_outbox
- id
- notification_type
- recipient_id
- payload
- status
- attempts
- scheduled_at
- sent_at
- timestamps
```

Canais:

- interno;
- push;
- e-mail;
- WhatsApp somente em evolução futura e via integração oficial.

---

## 20. Banco de dados

O banco oficial deve ser gerenciado por migrations Laravel.

Fonte principal:

```text
backend/database/migrations/
```

O SQL completo, quando existir, deve ser apenas referência:

```text
docs/database/eclesiapp_postgresql_reference.sql
```

Regras:

- usar foreign keys;
- usar índices explícitos;
- usar unique constraints;
- usar check constraints quando fizer sentido;
- evitar integridade somente em aplicação;
- triggers apenas para regras que realmente precisam existir no banco;
- migrations devem possuir `down()` seguro;
- não editar migrations já executadas em ambientes compartilhados;
- criar novas migrations para alterações;
- usar nomes claros;
- evitar campos genéricos sem semântica;
- timestamps em UTC;
- exibir datas no timezone da organização ou usuário;
- manter consistência de tipo de chave em todo o domínio.

---

## 21. Multi-organização e isolamento

Toda consulta deve respeitar o escopo da organização.

Nunca confiar em IDs recebidos do frontend sem verificar autorização e pertencimento.

Usar:

- policies;
- guards;
- query scopes;
- filtros explícitos;
- testes de isolamento;
- validações de vínculo entre organização, pessoa e recurso.

Um coordenador de uma organização não pode alterar dados internos de outra organização sem relação e permissão.

---

## 22. Segurança e privacidade

Dados potencialmente privados:

- telefone;
- e-mail pessoal;
- indisponibilidades;
- justificativas;
- observações internas;
- histórico de conflitos;
- dados de autenticação;
- tokens;
- localização precisa;
- convites privados;
- escala ainda não publicada.

Regras:

- princípio do menor privilégio;
- dados públicos separados de dados internos;
- logs sem senhas ou tokens;
- validação de upload;
- rate limiting;
- proteção contra enumeração de IDs;
- autorização em todas as rotas privadas;
- auditoria de ações críticas;
- secrets somente em variáveis de ambiente;
- nunca versionar `.env`;
- usar hash seguro de senhas;
- tokens revogáveis;
- evitar exposição excessiva em API Resources.

---

## 23. Docker

Serviços esperados:

```text
app
nginx
frontend
postgres
redis
queue
scheduler
mailpit
adminer
redisinsight
```

Portas locais sugeridas:

```text
Frontend: 5173
Backend: 8080
PostgreSQL: 5433
Redis: 6380
Mailpit: 8025
Adminer: 8081
RedisInsight: 5540
```

Dentro do Docker:

```text
PostgreSQL: postgres:5432
Redis: redis:6379
Mailpit: mailpit:1025
Backend: nginx:80
```

Nunca usar `localhost` entre containers.

PostgreSQL e Redis devem ter health checks.

Queue e scheduler devem aguardar dependências saudáveis.

O aplicativo mobile não precisa rodar dentro do Docker.

---

## 24. Testes

Toda regra de negócio relevante deve ter teste.

Prioridades:

1. criação de organização;
2. vínculo entre organizações;
3. cadastro de pessoa sem usuário;
4. vínculo posterior de usuário;
5. associação de pessoa a ministério;
6. publicação de evento;
7. criação de missão pública;
8. criação de missão privada;
9. candidatura;
10. convite;
11. aprovação;
12. designação;
13. conflito de horário;
14. conflito no mesmo dia;
15. exceção com justificativa;
16. indisponibilidade;
17. troca;
18. isolamento entre organizações;
19. autorização;
20. histórico e auditoria.

Tipos:

- unitários para domínio;
- feature tests para API;
- integração para PostgreSQL;
- testes de policies;
- testes de concorrência quando viável;
- testes do frontend para fluxos críticos;
- testes end-to-end apenas onde agregarem valor.

Não criar teste que apenas repita implementação.

---

## 25. Padrões de código

### PHP

- PSR-12;
- tipos explícitos;
- enums para estados;
- DTOs para entrada complexa;
- exceptions de domínio;
- actions para casos de uso;
- services pequenos e coesos;
- evitar métodos extensos;
- evitar arrays sem contrato em regras centrais;
- evitar helpers globais de domínio;
- evitar lógica de negócio em migrations, controllers ou views;
- usar transações nos casos críticos;
- tratar erros de forma previsível.

### TypeScript

- `strict` habilitado;
- evitar `any`;
- tipos de API centralizados;
- services separados de componentes;
- estado global somente quando necessário;
- componentes pequenos;
- lógica de negócio fora da camada visual;
- validação de formulários;
- tratamento explícito de loading, empty state e erro.

---

## 26. API

Regras:

- versionar API quando necessário;
- usar recursos REST de forma consistente;
- respostas padronizadas;
- erros com códigos estáveis;
- paginação;
- filtros explícitos;
- ordenação segura;
- evitar N+1;
- usar API Resources;
- validar requests com Form Requests;
- policies em todas as ações sensíveis;
- idempotência para operações críticas quando aplicável;
- não expor stack traces em produção.

---

## 27. Auditoria

Ações críticas devem ser auditadas:

- criação e alteração de organizações;
- publicação de eventos;
- criação e alteração de missões;
- convites;
- candidaturas;
- aprovação ou rejeição;
- designações;
- substituições;
- exceções de conflito;
- cancelamentos;
- mudanças de permissão;
- publicação de hinários.

A auditoria deve registrar:

- ator;
- organização;
- ação;
- entidade;
- ID da entidade;
- estado anterior;
- estado novo;
- data;
- justificativa, quando houver.

---

## 28. Git

Usar monorepo.

Branch principal:

```text
main
```

Branches:

```text
feature/*
fix/*
refactor/*
chore/*
docs/*
test/*
```

Commits convencionais:

```text
feat:
fix:
chore:
docs:
test:
refactor:
style:
perf:
```

Não misturar várias funcionalidades independentes no mesmo commit.

Não fazer push direto na `main` quando houver fluxo com Pull Request.

---

## 29. Fases de implementação

### Fase 1 — Fundação

- Docker;
- Laravel;
- Vue;
- autenticação;
- organizações;
- pessoas;
- usuários;
- papéis;
- permissões;
- migrations;
- testes base.

### Fase 2 — Escala interna

- eventos;
- tipos de evento;
- missões internas;
- vagas;
- designações;
- conflitos;
- planejamento mensal;
- publicação de escala.

### Fase 3 — Servos

- aplicativo mobile;
- consulta de escalas;
- indisponibilidades;
- notificações.

### Fase 4 — Trocas

- solicitações;
- aceite;
- aprovação;
- histórico;
- notificações.

### Fase 5 — Música

- catálogo;
- hinário;
- repertório;
- cifras;
- links;
- tons;
- observações.

### Fase 6 — Missões entre organizações

- ministérios independentes;
- relações entre organizações;
- missões públicas;
- missões privadas;
- candidaturas;
- convites;
- aprovação;
- vagas para organizações.

### Fase 7 — Expansão

- busca por localização;
- relatórios;
- exportação;
- Google Calendar;
- indicadores;
- distribuição equilibrada;
- outras integrações.

---

## 30. Restrições de implementação

Não fazer sem autorização:

- apagar banco;
- executar `migrate:fresh` em ambiente com dados;
- remover volumes Docker;
- alterar credenciais;
- reescrever histórico Git;
- fazer force push;
- remover migrations aplicadas;
- trocar stack principal;
- introduzir microsserviços;
- adicionar dependência pesada sem justificativa;
- expor informações privadas;
- ignorar regras de autorização;
- contornar testes quebrados;
- desabilitar validações para “fazer funcionar”.

---

## 31. Critérios para concluir uma tarefa

Uma tarefa só está concluída quando:

1. a implementação está funcional;
2. as regras de negócio foram respeitadas;
3. migrations foram criadas ou atualizadas corretamente;
4. testes relevantes foram criados;
5. testes existentes continuam passando;
6. lint e análise estática passam;
7. permissões foram verificadas;
8. tratamento de erros foi implementado;
9. documentação foi atualizada;
10. não houve exposição de secrets;
11. o código segue a arquitetura modular;
12. impactos e trade-offs foram registrados quando relevantes.

---

## 32. Primeiro objetivo recomendado

Antes de implementar funcionalidades avançadas, entregue um núcleo consistente com:

- organizações;
- relações entre organizações;
- pessoas;
- usuários;
- memberships;
- papéis;
- tipos de ministério;
- funções;
- eventos;
- missões internas;
- vagas;
- designações;
- conflitos;
- testes;
- autenticação e autorização.

As missões públicas, candidaturas e convites devem ser previstas no modelo, mas podem ser implementadas após o fluxo interno estar estável.

---

## 33. Instrução inicial sugerida para o Codex

```text
Leia integralmente o AGENTS.md e os documentos em docs/architecture e
docs/decisions.

Analise o estado atual do Eclesiapp, incluindo Docker, backend, frontend,
migrations, módulos, testes e documentação.

Não altere arquivos inicialmente.

Apresente:
1. diagnóstico da estrutura atual;
2. divergências em relação ao AGENTS.md;
3. riscos técnicos;
4. plano incremental para o primeiro MVP;
5. primeira tarefa recomendada com critérios de aceite.
```

---

## 34. Estado atual do projeto

Última atualização: 1º de agosto de 2026.

Esta seção deve ser atualizada ao concluir cada incremento relevante. Ela registra estado, não substitui as regras arquiteturais anteriores.

### Concluído

- fundação Docker com `app`, `nginx`, `frontend`, `postgres`, `redis`, `queue`, `scheduler` e `mailpit`;
- health checks, ambiente local, CI, lint, análise estática e testes base;
- autenticação por token Sanctum com cadastro, login e logout;
- separação entre `users` e `people`;
- organizações, hierarquia opcional e relações flexíveis;
- memberships e matriz inicial de papéis;
- policies, filtros explícitos e testes de isolamento organizacional;
- cadastro de pessoa sem conta por coordenação;
- auditoria transacional para organização, membership e relação sem dados pessoais privados;
- migrations com ULIDs, foreign keys, índices parciais, checks PostgreSQL e timestamps com timezone;
- painel Vue responsivo para cadastro, login, logout, listagem e criação de organizações;
- cliente HTTP e sessão tipados, com loading, vazio, sucesso, falha e expiração explícitos;
- área da organização com listagem e cadastro de pessoas sem criação automática de usuário;
- convite de conta de uso único, armazenado por hash, com expiração de 48 horas e envio após commit;
- aceite transacional do convite, verificação de e-mail, vínculo entre `people` e `users` e auditoria;
- fluxo completo no painel para envio pelo Mailpit e criação de acesso pelo link recebido;
- módulo `Ministries` com tipos de ministério e funções de serviço isolados por organização;
- competências pessoais atribuídas somente a membros ativos, com proteção contra vínculo cruzado;
- permissões de catálogo para owner/administrator e de atribuição para coordenação;
- auditoria transacional de criação, atribuição e remoção de funções;
- painel para catálogo de ministérios e gestão das competências de cada pessoa;
- módulos `Scheduling` e `Missions` com tipos de evento, locais, eventos internos, missões internas, vagas individuais e designações;
- prevenção global de sobreposição de horário, com bloqueio pessimista da pessoa, capacidade da vaga, qualificação e teste dedicado em PostgreSQL;
- isolamento organizacional, integridade composta e auditoria transacional para eventos e missões;
- painel administrativo para catálogos de agenda, eventos privados em rascunho, missões internas e múltiplas vagas individuais;
- painel administrativo para montar a escala por vaga, consultar pessoas qualificadas e criar designações;
- conversão explícita entre horário local da organização e UTC, com testes de deslocamento sazonal;
- indisponibilidades com intervalos informados pelo servo, consulta pela coordenação e caráter informativo sem bloqueio da designação;
- documentação de Identity, Organizations, Ministries, Scheduling, Missions, segurança, tenancy e painel administrativo.

### Em andamento

- limite diário configurável, exceções e publicação de escala;
- políticas configuráveis de conflito.

### Pendente para o primeiro MVP

1. recuperação de senha e verificação de e-mail;
2. autenticação web stateful com cookie `HttpOnly` antes de produção;
3. aceite ou rejeição bilateral de relações entre organizações;
4. inativação de membership, transferência de propriedade e histórico completo desses fluxos;
5. publicação e histórico de escala;
6. políticas configuráveis de conflito;
7. exceções autorizadas com justificativa;
8. notificações e outbox;
9. telas administrativas para indisponibilidades, publicação e demais fluxos internos;
10. ampliar a cobertura PostgreSQL e os cenários de concorrência;
11. endpoint mobile para consultar as próprias escalas, sem confirmação ou rejeição pelo servo;
12. aplicativo mobile dos servos com autenticação, escalas e indisponibilidades;
13. notificações push para publicação, alteração e cancelamento de escala;
14. testes dos fluxos críticos do aplicativo mobile;
15. piloto controlado, observabilidade e endurecimento de produção.

### Fora do primeiro MVP

- trocas de escala;
- música, hinário e repertório;
- missões públicas, candidaturas e convites entre organizações;
- geolocalização, relatórios e integrações externas.
