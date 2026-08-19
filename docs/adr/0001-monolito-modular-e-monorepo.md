# ADR 0001 — Monólito modular e monorepo

- Status: aceito
- Data: 19 de agosto de 2026

## Contexto

Agenda, autorização, escalas e conflitos precisam compartilhar transações e invariantes. Web e mobile devem consumir o mesmo contrato sem duplicar regras pastorais.

## Decisão

Adotar um monorepo com API Laravel organizada como monólito modular, uma aplicação React/Vite, um aplicativo Expo e pacotes TypeScript compartilhados. PostgreSQL é a autoridade de integridade; Redis atende filas e cache.

## Consequências

- operações críticas permanecem transacionais;
- módulos internos precisam preservar limites de domínio;
- tipos dos clientes serão gerados do OpenAPI;
- separação em serviços independentes exige novo ADR e evidência operacional.
