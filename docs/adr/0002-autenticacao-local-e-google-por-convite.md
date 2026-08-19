# ADR 0002 — Autenticação local e Google por convite

- **Status:** aceito
- **Data:** 19 de agosto de 2026

## Contexto

O MVP precisa permitir login tradicional e login com uma conta Google, sem abrir cadastro público ou transformar automaticamente qualquer conta Google em usuário do eclEZapp.

## Decisão

- manter login local com sessão web Sanctum por cookie `HttpOnly` e proteção CSRF;
- usar Laravel Socialite e OpenID Connect para o login Google;
- exigir e-mail verificado pelo Google;
- no primeiro login Google, vincular somente uma conta ativa já convidada cujo e-mail de acesso corresponda ao e-mail verificado;
- persistir o identificador estável `sub` do Google e usar esse vínculo nos acessos posteriores;
- não armazenar access token ou refresh token do Google;
- manter `GOOGLE_CLIENT_SECRET` apenas em variável de ambiente ou cofre de segredos.

## Consequências

- o login Google não cria contas e preserva o controle de entrada por convite;
- mudanças futuras no e-mail Google não quebram um vínculo já estabelecido pelo `sub`;
- não será possível acessar Gmail, Calendar ou outras APIs Google sem uma decisão futura e novo consentimento explícito;
- a validação ponta a ponta exige um cliente OAuth configurado fora do repositório.
