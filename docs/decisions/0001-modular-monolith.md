# ADR 0001 — Monólito modular em monorepositório

- Estado: aceito
- Data: 2026-07-22

## Contexto

O produto possui domínios relacionados e uma equipe inicial pequena. Microsserviços aumentariam custo operacional, consistência distribuída e tempo de entrega sem benefício comprovado.

## Decisão

Manter backend, frontend e mobile no mesmo repositório. Implantar o backend como um monólito modular com fronteiras de código explícitas e um único PostgreSQL.

## Consequências

- transações entre módulos continuam simples;
- CI e ambiente local permanecem acessíveis;
- dependências internas precisam ser revisadas para evitar um monólito acoplado;
- extração futura só será considerada com evidência de necessidade operacional ou de escala.
