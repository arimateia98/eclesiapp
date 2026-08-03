# Ambiente local de demonstração

## Finalidade

Este ambiente permite que pessoas testadoras e outros agentes validem o fluxo atual do MVP sem apagar dados locais. O seeder é idempotente, executa apenas com `APP_ENV=local` e pode ser repetido após novas migrations.

## Inicialização não destrutiva

```powershell
Copy-Item .env.example .env -ErrorAction SilentlyContinue
Copy-Item backend/.env.example backend/.env -ErrorAction SilentlyContinue
Copy-Item frontend/.env.example frontend/.env -ErrorAction SilentlyContinue
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

Não use `migrate:fresh`, não remova volumes e não altere as credenciais locais existentes.

## Acessos de demonstração

| Perfil | E-mail | Senha |
| --- | --- | --- |
| Coordenador | `coordenador@eclesiapp.local` | `Eclesiapp123!` |
| Servo | `servo@eclesiapp.local` | `Eclesiapp123!` |

Essas credenciais são exclusivamente locais e não devem ser reutilizadas em ambientes compartilhados ou de produção.

## Endereços

- painel administrativo: <http://localhost:5173>;
- API: <http://localhost:8080/api/v1/health>;
- Mailpit: <http://localhost:8025>.

Se a porta `8080` já estiver ocupada, altere apenas o `BACKEND_PORT` do `.env` local e recrie o Nginx com `docker compose up -d --force-recreate nginx`. O painel continua acessando a API pelo proxy interno em `/api/v1`.

## Cenário preparado

O `DemoSeeder` cria ou atualiza:

- Comunidade Demonstração;
- coordenador com papel de proprietário;
- servo como membro ativo;
- tipo de ministério Liturgia e função Leitor;
- missa publicada para o próximo domingo;
- missão preenchida e designação confirmada para o servo;
- intervalo informativo de indisponibilidade no dia anterior.

O coordenador pode entrar no painel e continuar o planejamento. O servo já pode autenticar pela API e consultar `GET /api/v1/me/assignments` e `GET /api/v1/me/unavailabilities`.

## Verificação rápida

```powershell
docker compose ps
docker compose exec app php artisan migrate:status
docker compose exec app composer test
docker compose exec frontend npm run test
```

## Orientação para agentes

1. Leia `AGENTS.md`, `docs/architecture/` e `docs/decisions/` antes de alterar código.
2. Confirme que a árvore Git está limpa e crie uma branch específica por incremento.
3. Preserve o seeder idempotente e restrito ao ambiente local.
4. Atualize a seção de estado do `AGENTS.md` e a documentação do módulo ao concluir um incremento.
5. Valide backend, frontend, Compose e PostgreSQL conforme o risco da alteração.
6. Use Pull Request; nunca envie diretamente para `main`.

## Limites atuais visíveis no teste

- o painel ainda não possui telas para publicar escala ou consultar indisponibilidades;
- o aplicativo mobile ainda não foi criado;
- notificações e outbox ainda não estão disponíveis;
- o endpoint de minhas escalas pode ser validado por API até existir o cliente mobile.
