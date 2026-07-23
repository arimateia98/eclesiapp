# Primeiro MVP

## Objetivo

Permitir que um coordenador planeje e publique uma escala interna sem criar conflito de horário para uma pessoa que serve em mais de uma organização.

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
12. planejamento e consulta pelo painel web.

## Fora do MVP

- aplicativo mobile e push;
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
7. endurecimento e piloto.

Cada incremento exige migration reversível, autorização, teste de isolamento, tratamento previsível de erro e atualização documental.

## Progresso

- fundação executável: concluída;
- Identity e Organizations: primeiro recorte concluído, incluindo autenticação por token, criação e isolamento;
- vínculo seguro por convite para pessoas pré-cadastradas: pendente;
- Ministries e etapas posteriores: pendentes.
