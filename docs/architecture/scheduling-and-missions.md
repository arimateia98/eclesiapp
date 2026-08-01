# Scheduling e Missions internas

## Objetivo do incremento

O primeiro recorte de `Scheduling` e `Missions` permite que uma organização planeje eventos e as vagas individuais necessárias para atendê-los. Missões públicas, convites, candidaturas, publicação e designações continuam fora deste recorte.

## Modelo

- `event_types`: catálogo de tipos de evento próprio da organização;
- `locations`: locais da organização, com timezone explícito;
- `events`: agenda genérica, separando organização publicadora e anfitriã;
- `missions`: oportunidade de serviço vinculada ao evento e a um tipo de ministério;
- `mission_slots`: quantidade de pessoas necessária para cada função de serviço.

Eventos internos são criados como `private` e `draft`, com publicadora e anfitriã iguais à organização ativa. Missões internas também são `private` e `draft`, usam a política `coordinator_assignment` e têm a própria organização como alvo. Mudanças de estado serão casos de uso explícitos em incrementos posteriores; criar um rascunho nunca publica uma escala silenciosamente.

## Integridade

- eventos exigem término posterior ao início na aplicação e em check PostgreSQL;
- chaves estrangeiras compostas impedem tipo de evento e local de outra organização;
- missões só podem usar evento e tipo de ministério da organização ativa;
- vagas individuais só aceitam funções ativas do ministério escolhido;
- a mesma função pode aparecer uma única vez em cada missão;
- quantidades devem ser positivas na aplicação e no PostgreSQL;
- catálogos bloqueiam a organização antes da verificação de unicidade;
- criação de evento bloqueia tipo e local; criação de missão bloqueia evento, ministério e funções;
- criação de catálogo, evento e missão gera auditoria na mesma transação.

Endereço do local e descrição do evento ou missão não são copiados para a auditoria. O registro guarda apenas identificadores, intervalo, timezone e estados operacionais necessários à rastreabilidade.

## Permissões

| Operação | Owner | Administrator | Coordinator | Member/Guest |
| --- | --- | --- | --- | --- |
| Consultar catálogos e agenda da organização acessível | sim | sim | sim | sim |
| Criar tipo de evento ou local | sim | sim | não | não |
| Criar evento interno | sim | sim | sim | não |
| Criar missão e vagas individuais | sim | sim | sim | não |

O frontend pode ocultar ações conforme `current_user_role`, mas a autorização efetiva é repetida por policy e action no backend. Toda consulta aplica `organization_id` ou `publisher_organization_id` explicitamente.

## Contrato HTTP

Todas as rotas exigem Sanctum e começam em `/api/v1`.

| Método | Rota | Uso |
| --- | --- | --- |
| `GET`, `POST` | `/organizations/{organization}/event-types` | listar ou criar tipos de evento |
| `GET`, `POST` | `/organizations/{organization}/locations` | listar ou criar locais |
| `GET`, `POST` | `/organizations/{organization}/events` | listar ou criar eventos internos |
| `GET` | `/organizations/{organization}/events/{event}` | consultar evento com missões e vagas |
| `GET`, `POST` | `/organizations/{organization}/events/{event}/missions` | listar ou criar missões internas |

Datas recebidas devem representar um instante ISO 8601. A API normaliza o valor para UTC e retorna ISO 8601 em UTC; o cliente é responsável por apresentar no timezone da organização.

## Limites atuais

Ainda não existem edição, cancelamento, publicação, recorrência, locais compartilhados, eventos sediados por outra organização ou vagas para organizações. Designações, indisponibilidades e conflitos serão o próximo incremento e seguirão o bloqueio pessimista definido no ADR 0004.
