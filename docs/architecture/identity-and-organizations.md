# Identity e Organizations

## Estado do incremento

O primeiro recorte do MVP implementa autenticação por token Sanctum, perfis de pessoa, organizações, memberships, relações entre organizações e vínculo posterior de conta. `users` continua sendo a identidade de autenticação e `people` representa a pessoa que serve; o vínculo é opcional e único.

O cadastro público cria as duas entidades na mesma transação. Pessoas cadastradas por coordenação permanecem sem `user_id` até aceitarem um convite seguro enviado ao e-mail registrado.

## Casos de uso disponíveis

- registrar conta e perfil de pessoa;
- autenticar e revogar o token corrente;
- consultar ou criar o próprio perfil quando a conta foi provisionada externamente;
- criar uma organização e receber o papel `owner` atomicamente;
- listar organizações públicas ou vinculadas à pessoa autenticada;
- consultar uma organização respeitando visibilidade e membership;
- listar membros ativos de uma organização gerenciada;
- cadastrar uma pessoa sem conta como membro;
- convidar essa pessoa para criar acesso;
- aceitar o convite e vincular a conta à pessoa de forma atômica;
- criar relação entre duas organizações administradas pelo mesmo ator.

## Matriz inicial de papéis

| Papel | Ver organização privada | Listar/cadastrar membro | Convidar membro | Papéis que pode conceder | Criar relação |
| --- | --- | --- | --- | --- | --- |
| `owner` | sim | sim | sim | todos | sim, se gerenciar os dois lados |
| `administrator` | sim | sim | sim | `coordinator`, `member`, `guest` | sim, se gerenciar os dois lados |
| `coordinator` | sim | sim | sim | `member`, `guest` | não |
| `member` | sim | não | não | nenhum | não |
| `guest` | sim | não | não | nenhum | não |

Uma relação não concede acesso implícito aos dados internos da organização relacionada.

## Convite de conta

- o convite só pode ser criado por quem gerencia membros;
- a pessoa precisa ter membership ativo na organização e e-mail válido;
- não é permitido convite para pessoa já vinculada ou com outro convite pendente;
- o token aleatório possui 64 caracteres, é enviado por e-mail e somente seu hash SHA-256 é persistido;
- o convite expira em 48 horas e é aceito uma única vez;
- aceite, criação do usuário, verificação do e-mail, vínculo da pessoa e atualização do convite ocorrem na mesma transação;
- o e-mail é enfileirado somente após o commit;
- token e endereço de e-mail não são registrados na auditoria.

O recebimento do link no e-mail do perfil é a confirmação inicial de posse do endereço. Recuperação de senha e fluxos para vincular uma pessoa a uma conta já existente permanecem fora deste incremento.

## Integridade e concorrência

- ULIDs são usados em todas as entidades do recorte;
- foreign keys preservam vínculo e histórico;
- apenas um membership ativo é permitido por pessoa e organização;
- apenas uma relação ativa do mesmo tipo é permitida entre o mesmo par;
- apenas um convite pendente é permitido por pessoa;
- checks PostgreSQL impedem autorreferência e estados incoerentes de aceite;
- criação da organização e membership do proprietário ocorre em uma transação;
- criação de relações bloqueia as duas organizações em ordem determinística;
- aceite de convite bloqueia convite e pessoa antes de criar a conta;
- organização, membership, relação, convite e vínculo de conta geram auditoria transacional sem dados pessoais privados.

## Contrato HTTP

As rotas começam em `/api/v1`. Erros de autenticação, autorização, validação e domínio retornam um campo `code` estável. Telefone e e-mail pessoal aparecem somente nos recursos internos autorizados, nunca na listagem pública de organizações.

| Método | Rota | Proteção |
| --- | --- | --- |
| `POST` | `/auth/register` | limite de tentativas |
| `POST` | `/auth/login` | limite de tentativas |
| `POST` | `/auth/account-invitations/accept` | token de convite + limite de tentativas |
| `DELETE` | `/auth/token` | Sanctum |
| `GET`, `POST` | `/profile` | Sanctum |
| `GET`, `POST` | `/organizations` | Sanctum |
| `GET` | `/organizations/{organization}` | Sanctum + policy |
| `GET`, `POST` | `/organizations/{organization}/members` | Sanctum + policy |
| `POST` | `/organizations/{organization}/members/{person}/account-invitations` | Sanctum + policy |
| `POST` | `/organizations/{organization}/relationships` | Sanctum + policy nos dois lados |

## Próximos limites

Ainda não há edição, inativação, transferência de propriedade, vínculo com conta já existente ou aceite bilateral de relações. Esses fluxos exigirão política e auditoria próprias antes de serem disponibilizados.

A autenticação bearer em `sessionStorage` é adequada somente ao piloto local. O painel de produção deverá usar o modo stateful do Sanctum com cookie seguro.
