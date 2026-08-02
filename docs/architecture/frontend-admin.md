# Painel administrativo

## Estado do incremento

O painel Vue oferece um fluxo navegável para:

- verificar a disponibilidade da API;
- criar conta e pessoa separadas;
- autenticar com e-mail e senha;
- restaurar a sessão durante a aba atual;
- encerrar a sessão e revogar o token;
- listar e criar organizações;
- abrir a área de uma organização autorizada;
- listar membros ativos;
- cadastrar uma pessoa sem criar usuário;
- criar tipos de ministério e funções de serviço conforme o papel do usuário;
- consultar, atribuir e remover competências das pessoas;
- criar tipos de evento e locais conforme o papel do usuário;
- planejar eventos privados em rascunho no timezone da organização;
- consultar eventos e suas missões internas;
- criar missões com uma ou mais vagas individuais por função de serviço;
- abrir a escala de cada missão e acompanhar o preenchimento das vagas;
- consultar pessoas qualificadas por vaga e criar designações com retorno explícito de conflitos;
- enviar um convite de acesso para a pessoa cadastrada;
- aceitar o convite por link e vincular uma nova conta ao perfil existente.

O fluxo usa composição de telas em `App.vue`, sem roteador ou estado global. A área da organização alterna localmente entre agenda e pessoas; evento, missão e vaga selecionados não são persistidos na URL. A URL só carrega o token do convite, removido com `history.replaceState` após o aceite ou cancelamento. Um roteador deve ser introduzido junto à publicação e à consulta persistente da escala, quando evento, missão ou planejamento precisarem de link direto e navegação pelo histórico.

## Organização do código

- `src/services/api.ts`: cliente HTTP tipado e normalização de erros;
- `src/services/session.ts`: armazenamento e validação do contrato de sessão;
- `src/components/AuthPanel.vue`: cadastro e login;
- `src/components/InvitationAcceptancePanel.vue`: criação de acesso a partir do convite;
- `src/components/OrganizationDashboard.vue`: listagem e criação de organizações;
- `src/components/OrganizationWorkspace.vue`: membros, cadastro de pessoa e convite;
- `src/components/MinistryCapabilitiesPanel.vue`: catálogo e competências pessoais;
- `src/components/SchedulingPanel.vue`: catálogos de agenda, eventos, missões, vagas e designações;
- `src/services/dateTime.ts`: interpretação do horário de parede no timezone da organização e conversão para UTC;
- `src/App.vue`: coordenação de sessão, navegação local, carregamento e expiração.

Componentes visuais não chamam `fetch` diretamente. O serviço da API centraliza headers, envelopes, autenticação e erros previsíveis. Os componentes emitem intenções e a aplicação coordena o estado assíncrono.

## Segurança da sessão e do convite

O token de sessão fica em `sessionStorage`, não em `localStorage`, e é removido no logout ou em qualquer resposta `401`. Isso atende o piloto local e evita persistência entre sessões completas do navegador, mas continua exposto a JavaScript em caso de XSS.

O token de convite chega pela URL somente durante o aceite. Ele é de uso único, expira no backend e é apagado da barra do navegador após autenticação ou cancelamento. O frontend nunca persiste esse token.

Antes de produção, o painel web deve migrar para autenticação stateful do Sanctum com cookie `HttpOnly`, `Secure`, `SameSite` e proteção CSRF. Tokens bearer permanecem apropriados para clientes mobile e integrações autorizadas.

## Experiência e acessibilidade

- formulários usam labels, autocomplete e mensagens com `role="alert"`;
- loading, vazio, sucesso, falha de API e sessão expirada possuem estados explícitos;
- formulários preservam os dados quando a API rejeita a operação;
- ações de catálogo e planejamento são apresentadas conforme o papel atual, sem substituir a autorização do backend;
- a seleção de uma vaga consulta somente memberships ativos com a competência exigida;
- conflitos de horário e capacidade retornados pela API preservam a seleção para correção;
- horários são exibidos no timezone da organização e enviados como instantes UTC;
- ações de convite sem e-mail válido permanecem indisponíveis;
- layout é responsivo a partir de 320 px;
- animações respeitam `prefers-reduced-motion`;
- cores, foco e botões seguem o mesmo sistema visual.

## Teste manual local

1. Entre no painel em <http://localhost:5173>.
2. Abra uma organização e cadastre uma pessoa com e-mail.
3. Envie o convite na linha da pessoa.
4. Abra o e-mail em <http://localhost:8025>.
5. Use o link recebido para criar a senha e entrar com a nova conta.

No ambiente local, a fila deve estar ativa para que o e-mail apareça no Mailpit.
