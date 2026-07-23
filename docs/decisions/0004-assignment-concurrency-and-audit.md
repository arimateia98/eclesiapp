# ADR 0004 — Concorrência e auditoria de designações

- Estado: aceito
- Data: 2026-07-22

## Contexto

Duas coordenações podem tentar escalar a mesma pessoa simultaneamente. Uma validação feita fora da transação permite que ambas observem um estado ainda válido e persistam um conflito.

## Decisão

A criação, troca ou substituição de uma designação ocorre em transação PostgreSQL. A action bloqueia a linha da pessoa com `FOR UPDATE`, carrega eventos e indisponibilidades relevantes, valida sobreposição e política diária, e só então grava.

Exceções exigem permissão específica e justificativa. A mudança e seu registro de auditoria são persistidos na mesma transação. Notificações futuras partem de uma outbox transacional.

## Consequências

- a ordem de aquisição de locks deve permanecer consistente;
- testes de integração usam PostgreSQL real;
- retries podem ser necessários em deadlock ou falha serializável;
- uma alteração publicada nunca sobrescreve silenciosamente o histórico anterior.
