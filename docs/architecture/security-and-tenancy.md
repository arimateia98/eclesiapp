# Segurança e isolamento organizacional

## Princípio

Autenticação responde quem é o usuário; membership e policy respondem em qual organização e com qual papel ele pode agir. Nenhum identificador enviado pelo cliente concede acesso por si só.

## Contexto organizacional

Rotas privadas deverão resolver uma organização ativa vinculada ao usuário autenticado. Actions e queries recebem esse contexto explicitamente. Scopes automáticos podem reduzir repetição, mas não substituem policies nem validação de relacionamento entre recursos.

## Controles obrigatórios

- policies em leitura e escrita privadas;
- route model binding escopado quando houver relação pai-filho;
- Form Requests para forma e autorização da entrada;
- API Resources distintos para dados públicos e internos;
- testes negativos entre organizações em cada novo recurso;
- rate limiting em autenticação e operações sensíveis;
- tokens revogáveis pelo Sanctum;
- logs sem senha, token, justificativa sensível ou indisponibilidade detalhada;
- portas de PostgreSQL e Redis limitadas ao loopback no ambiente local;
- debug e ferramentas administrativas desativados em produção.

## Dados pessoais

Telefone, e-mail pessoal, indisponibilidade, justificativas e observações internas são privados por padrão. Listagens públicas nunca reutilizam resources internos.

## Concorrência

Designações e substituições bloqueiam a linha da pessoa dentro de transação antes de validar conflitos. Exceções exigem permissão específica e justificativa não vazia. A decisão completa está no ADR 0004.

## Secrets

Arquivos `.env` não são versionados. Exemplos contêm apenas valores locais não sensíveis. Ambientes compartilhados devem injetar secrets pelo mecanismo da plataforma e rotacioná-los independentemente do código.
