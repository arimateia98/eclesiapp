# ADR 0002 — Pessoa, usuário, identificadores e tempo

- Estado: aceito
- Data: 2026-07-22

## Contexto

Uma pessoa pode ser cadastrada e escalada antes de criar uma conta. Recursos também circularão entre clientes web e mobile, e conflitos dependem de comparação temporal consistente.

## Decisão

- `people` e `users` são entidades distintas com vínculo opcional posterior;
- entidades do domínio usam ULID como chave primária;
- tabelas técnicas sem identidade externa podem usar chaves numéricas;
- timestamps representam instantes em UTC;
- timezone de organização ou usuário é usado apenas para interpretar entrada e formatar saída.

## Consequências

- o vínculo pessoa/usuário requer unicidade e fluxo explícito;
- foreign keys polimórficas para entidades do domínio devem suportar ULID;
- testes de borda de dia usam o timezone configurado, nunca o timezone do banco;
- PostgreSQL, PHP e containers operam em UTC.
