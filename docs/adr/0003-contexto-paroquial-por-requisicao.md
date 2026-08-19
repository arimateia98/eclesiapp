# ADR 0003 — Contexto paroquial por sessão ou requisição

- **Status:** aceito
- **Data:** 19 de agosto de 2026

## Contexto

Uma mesma conta pode possuir vínculos com mais de uma paróquia. Persistir uma única paróquia ativa no usuário causaria interferência entre navegador, aplicativo mobile e sessões simultâneas, além de facilitar consultas fora do escopo correto.

## Decisão

- manter o contexto paroquial fora da tabela de usuários;
- na web, persistir a seleção apenas na sessão autenticada;
- permitir `X-Parish-Id` em clientes sem estado;
- revalidar em cada requisição se usuário, vínculo e paróquia continuam ativos;
- resolver automaticamente apenas quando existir exatamente um vínculo ativo;
- exigir seleção explícita quando houver mais de um vínculo;
- retornar papéis somente quando estiverem vigentes na paróquia e na data corrente;
- aplicar o middleware `active.parish` antes das Policies específicas dos recursos paroquiais.

## Consequências

- sessões simultâneas podem operar em paróquias diferentes sem alterar o usuário;
- IDs enviados pelo cliente nunca constituem autorização;
- suspensão ou encerramento de vínculo passa a valer imediatamente na próxima requisição;
- futuras rotas paroquiais precisam declarar explicitamente o middleware e a Policy da ação;
- o aplicativo mobile poderá enviar o contexto em cabeçalho junto ao token Sanctum.
