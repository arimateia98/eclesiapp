# ADR 0003 — Autorização multi-organização explícita

- Estado: aceito
- Data: 2026-07-22

## Contexto

Usuários e pessoas podem participar de várias organizações, enquanto organizações também possuem hierarquia e relacionamentos flexíveis. Um filtro global simples não cobre essas combinações com segurança.

## Decisão

Toda operação privada recebe um contexto de organização ativa. Memberships, relacionamentos e policies determinam a autorização. Queries aplicam o contexto explicitamente e testes exercitam acesso cruzado negado.

Scopes automáticos são auxiliares e não a única barreira. IDs do cliente são sempre revalidados contra a organização e o caso de uso.

## Consequências

- actions e queries carregam contexto adicional;
- jobs serializam ator e organização necessários, sem depender de estado HTTP;
- recursos internos e públicos usam projeções diferentes;
- cada novo endpoint privado exige pelo menos um teste negativo entre organizações.
