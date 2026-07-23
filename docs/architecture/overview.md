# Visão de arquitetura

## Contexto

O Eclesiapp é um monorepositório com uma API Laravel, um painel Vue e, futuramente, um aplicativo mobile. O primeiro MVP atende o fluxo interno de planejamento e publicação de escalas.

## Estilo arquitetural

O backend é um monólito modular. Cada módulo contém domínio, aplicação, infraestrutura e HTTP, mas todos são implantados como uma única aplicação e usam o mesmo PostgreSQL.

```text
Identity
   ↓
Organizations
   ↓
Ministries
   ↓
Scheduling ← Missions
   ↓
Notifications
```

As setas representam dependências permitidas. Comunicação inversa deve ocorrer por eventos ou contratos explícitos, nunca por acesso a detalhes internos de outro módulo.

## Módulos planejados

- `Identity`: usuários, pessoas, autenticação e vínculo posterior entre pessoa e conta;
- `Organizations`: organizações, relacionamentos, memberships, papéis e contexto organizacional;
- `Ministries`: tipos de ministério, funções de serviço e capacidades das pessoas;
- `Scheduling`: eventos, indisponibilidades, políticas de conflito e designações;
- `Missions`: missões internas, vagas e participação; convites públicos ficam fora do MVP;
- `Notifications`: outbox e entrega assíncrona;
- `Music`: catálogo, hinário e repertório, fora do MVP.

`app/Shared` recebe apenas elementos realmente transversais, como resposta de saúde, relógio, auditoria e contratos técnicos.

O recorte implementado de identidade, organizações, papéis e relações está detalhado em [Identity e Organizations](identity-and-organizations.md).
O catálogo de ministérios, funções de serviço e capacidades pessoais está detalhado em [Ministries](ministries.md).
O fluxo web e o tratamento atual de sessão estão detalhados em [Painel administrativo](frontend-admin.md).

## Regras de dependência

- controllers validam o protocolo e delegam o caso de uso;
- actions delimitam transações e coordenam domínio e persistência;
- policies autorizam toda operação privada;
- queries complexas ficam isoladas e sempre recebem contexto organizacional;
- integrações externas pertencem à infraestrutura;
- models não substituem actions ou services de domínio;
- IDs recebidos por HTTP nunca constituem autorização.

## Persistência e tempo

- PostgreSQL é a fonte oficial e migrations Laravel são a única forma de alterar o esquema;
- entidades do domínio usam ULID;
- tabelas estritamente técnicas podem usar identificadores numéricos;
- instantes são persistidos e processados em UTC;
- timezone da organização ou usuário é aplicado somente na entrada e apresentação;
- foreign keys, índices, unicidade e checks complementam as regras da aplicação.

## API e frontend

A API começa em `/api/v1`, usa envelopes `data` e erros JSON. Sanctum será usado para autenticação do painel. Tipos de API ficam centralizados no frontend e chamadas HTTP não são realizadas diretamente por componentes visuais.

## Assincronia

Notificações e integrações usam fila Redis. Eventos que não podem ser perdidos devem ser gravados em uma outbox na mesma transação da mudança de domínio.

## Observabilidade

Logs devem ser estruturados, sem tokens ou dados pessoais desnecessários. Ações críticas carregam ator, organização, entidade e identificador de correlação. `/up` mede a vida do processo e `/api/v1/health` oferece o contrato público mínimo do serviço.
