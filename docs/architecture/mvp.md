# Primeiro MVP

## Objetivo

Permitir que um coordenador planeje e publique uma escala interna sem criar conflito de horário e que o servo consulte as próprias escalas pelo aplicativo mobile.

## Dentro do MVP

1. autenticação do coordenador;
2. organizações e relacionamentos;
3. pessoas separadas de usuários;
4. memberships e papéis;
5. tipos de ministério e funções de serviço;
6. eventos e missões internas;
7. vagas individuais;
8. designações;
9. conflito de sobreposição e limite diário configurável;
10. exceção autorizada com justificativa;
11. auditoria de ações críticas;
12. planejamento e consulta pelo painel web;
13. aplicativo mobile dos servos;
14. consulta das próprias escalas;
15. cadastro de indisponibilidades;
16. notificações push para publicação e alteração de escala.

## Fora do MVP

- trocas de escala;
- missões públicas, candidaturas e convites;
- hinário e repertório;
- geolocalização, relatórios e integrações externas.

O modelo não deve impedir essas evoluções, mas elas não geram endpoints, telas ou tabelas antecipadas sem um caso de uso do MVP.

## Sequência de entrega

1. fundação executável e decisões;
2. Identity e Organizations;
3. Ministries;
4. Events e Missions internas;
5. Assignments e conflito concorrente;
6. painel administrativo;
7. publicação, indisponibilidades e notificações;
8. aplicativo mobile dos servos;
9. endurecimento e piloto.

Cada incremento exige migration reversível, autorização, teste de isolamento, tratamento previsível de erro e atualização documental.

## Progresso

- fundação executável: concluída;
- Identity e Organizations: primeiro recorte concluído, incluindo autenticação por token, criação e isolamento;
- vínculo seguro por convite para pessoas pré-cadastradas: concluído;
- Ministries: concluído no recorte de catálogo, funções e competências pessoais;
- Events e Missions internas: concluídos no recorte de catálogo, agenda privada em rascunho e vagas individuais;
- painel de Events e Missions internas: concluído com timezone organizacional, múltiplas vagas e permissões por papel;
- Assignments e conflito de sobreposição: concluídos no backend, com qualificação, capacidade da vaga, isolamento multi-organização, bloqueio pessimista e teste PostgreSQL dedicado;
- interface de designações: concluída com preenchimento por vaga, consulta eficiente de pessoas qualificadas e tratamento dos erros de domínio;
- indisponibilidades informativas, consultáveis pela coordenação sem bloquear a escala: concluídas no backend;
- publicação transacional de escalas completas, confirmação das designações e auditoria: concluídas no backend;
- limite diário, exceções e histórico de alterações posteriores à publicação: pendentes;
- contrato mobile para consulta das próprias escalas: pendente;
- aplicativo mobile e notificações push: pendentes.
