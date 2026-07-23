# Identity e Organizations

## Estado do incremento

O primeiro recorte do MVP implementa autenticação por token Sanctum, perfis de pessoa, organizações, memberships e relações entre organizações. `users` continua sendo a identidade de autenticação e `people` representa a pessoa que serve; o vínculo é opcional e único.

O cadastro público cria as duas entidades na mesma transação. Pessoas cadastradas por coordenação permanecem sem `user_id` até existir um fluxo seguro de convite e confirmação de identidade.

## Casos de uso disponíveis

- registrar conta e perfil de pessoa;
- autenticar e revogar o token corrente;
- consultar ou criar o próprio perfil quando a conta foi provisionada externamente;
- criar uma organização e receber o papel `owner` atomicamente;
- listar organizações públicas ou vinculadas à pessoa autenticada;
- consultar uma organização respeitando sua visibilidade e membership;
- cadastrar uma pessoa sem conta como membro;
- criar relação entre duas organizações administradas pelo mesmo ator.

## Matriz inicial de papéis

| Papel | Ver organização privada | Cadastrar membro | Papéis que pode conceder | Criar relação |
| --- | --- | --- | --- | --- |
| `owner` | sim | sim | todos | sim, se gerenciar os dois lados |
| `administrator` | sim | sim | `coordinator`, `member`, `guest` | sim, se gerenciar os dois lados |
| `coordinator` | sim | sim | `member`, `guest` | não |
| `member` | sim | não | nenhum | não |
| `guest` | sim | não | nenhum | não |

Uma relação não concede acesso implícito aos dados internos da organização relacionada.

## Integridade e concorrência

- ULIDs são usados em todas as entidades do recorte;
- foreign keys preservam vínculo e histórico;
- apenas um membership ativo é permitido por pessoa e organização;
- apenas uma relação ativa do mesmo tipo é permitida entre o mesmo par;
- checks PostgreSQL impedem autorreferência e datas de encerramento incoerentes;
- criação da organização e membership do proprietário ocorre em uma transação;
- criação de relações bloqueia as duas organizações em ordem determinística.
- criação de organização, concessão de membership e criação de relação geram auditoria na mesma transação, sem telefone, e-mail ou token.

## Contrato HTTP

As rotas começam em `/api/v1`. Erros de autenticação, autorização, validação e domínio retornam um campo `code` estável. Telefone e e-mail pessoal aparecem somente no resource interno de pessoa, nunca na listagem de organizações.

| Método | Rota | Proteção |
| --- | --- | --- |
| `POST` | `/auth/register` | limite de tentativas |
| `POST` | `/auth/login` | limite de tentativas |
| `DELETE` | `/auth/token` | Sanctum |
| `GET`, `POST` | `/profile` | Sanctum |
| `GET`, `POST` | `/organizations` | Sanctum |
| `GET` | `/organizations/{organization}` | Sanctum + policy |
| `POST` | `/organizations/{organization}/members` | Sanctum + policy |
| `POST` | `/organizations/{organization}/relationships` | Sanctum + policy |

## Próximos limites

O fluxo de vínculo posterior entre uma pessoa pré-cadastrada e uma nova conta não aceita simples envio de ID, pois isso permitiria tomada de identidade. Ele deve usar convite de uso único, expiração e confirmação de e-mail antes de ser exposto.

Ainda não há edição, inativação, transferência de propriedade ou aceite bilateral de relações. Esses fluxos exigirão auditoria antes de serem disponibilizados.
