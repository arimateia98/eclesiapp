# Ministries

## Objetivo do incremento

O módulo `Ministries` descreve em quais funções uma pessoa está apta a servir sem criar tabelas específicas para músicos, leitores, salmistas ou outras pastorais. O catálogo é próprio de cada organização e será reutilizado posteriormente por vagas e designações.

## Modelo

- `ministry_types`: agrupamentos organizacionais como Liturgia, Música e Acolhida;
- `service_functions`: funções concretas como Leitor, Salmista, Vocalista e Ministro da Comunhão;
- `person_functions`: competência que liga uma pessoa a uma função de serviço.

Uma função pertence ao mesmo tempo à organização e a um tipo de ministério. Uma chave estrangeira composta impede que uma função use um tipo de outra organização. A mesma proteção composta liga `person_functions` à função dentro do escopo organizacional.

O vínculo de competência só pode ser criado quando a pessoa possui membership ativo na organização e o tipo e a função estão ativos. A pessoa continua sendo a identidade global: o catálogo pode variar por organização, mas não existe uma cópia da pessoa para cada pastoral.

## Permissões

| Operação | Owner | Administrator | Coordinator | Member/Guest |
| --- | --- | --- | --- | --- |
| Consultar catálogo da organização acessível | sim | sim | sim | sim |
| Criar tipo de ministério | sim | sim | não | não |
| Criar função de serviço | sim | sim | não | não |
| Consultar competências de membros | sim | sim | sim | não |
| Atribuir ou remover competência | sim | sim | sim | não |

O frontend usa `current_user_role` retornado no recurso de organização apenas para apresentar as ações corretas. Policies e actions repetem a autorização no backend; o valor recebido pelo cliente nunca concede acesso.

## Concorrência e auditoria

- criação de catálogo bloqueia a organização antes de verificar unicidade;
- atribuição bloqueia a pessoa antes de verificar duplicidade;
- chaves únicas impedem duplicidade mesmo diante de concorrência;
- atribuição e remoção verificam explicitamente organização, membership, tipo e função;
- criação de tipo, criação de função, atribuição e remoção geram auditoria na mesma transação;
- auditoria registra IDs e estado operacional, sem e-mail, telefone ou outros dados pessoais.

## Contrato HTTP

Todas as rotas abaixo exigem Sanctum e começam em `/api/v1`.

| Método | Rota | Uso |
| --- | --- | --- |
| `GET`, `POST` | `/organizations/{organization}/ministry-types` | listar ou criar tipos |
| `GET`, `POST` | `/organizations/{organization}/service-functions` | listar ou criar funções |
| `GET`, `POST` | `/organizations/{organization}/members/{person}/functions` | listar ou atribuir competências |
| `DELETE` | `/organizations/{organization}/members/{person}/functions/{serviceFunction}` | remover competência |

Listagens aceitam `per_page` até 100. Funções também podem ser filtradas por `ministry_type_id`.

## Limites atuais

Ainda não existem edição e inativação do catálogo, equivalência de funções entre organizações ou fluxo para a própria pessoa solicitar uma competência. Essas operações exigirão regras próprias e não são antecipadas para o primeiro fluxo de escala interna.
