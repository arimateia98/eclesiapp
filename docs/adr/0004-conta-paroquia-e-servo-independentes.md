# ADR 0004 — Conta, paróquia e servo independentes

- **Status:** aceito
- **Data:** 19 de agosto de 2026
- **Supera:** ADR 0002 no requisito de convite prévio para criação de conta

## Contexto

Uma pessoa pode criar uma conta antes de participar de uma paróquia. Possuir credenciais não significa ter autorização paroquial nem estar disponível para escalas. O MVP precisa concentrar o fluxo pastoral em servos, preservando acesso administrativo para padres e coordenadores.

## Decisão

- permitir autocadastro local ou pelo Google sem criar vínculo paroquial;
- manter toda conta ligada a uma pessoa, mas não criar `parish_user_memberships`, papéis ou `servants` automaticamente;
- tratar `users` exclusivamente como credenciais e autorização;
- tratar `servants` como vínculo escalável entre pessoa e paróquia, sem dependência obrigatória de usuário;
- permitir que padres e administradores tenham usuário sem serem servos;
- permitir que um coordenador ou padre também tenha `servants` somente se puder ser escalado;
- impedir acesso a recursos paroquiais enquanto a conta não possuir vínculo e papel vigentes;
- não armazenar tokens OAuth do Google.

## Consequências

- usuário sem paróquia autentica e consulta apenas seu próprio perfil;
- criar conta nunca concede papel, paróquia ou condição de servo;
- criar servo nunca gera senha ou usuário automaticamente;
- escalas futuras referenciarão `servants`, nunca `users`;
- convites futuros terão como finalidade principal conceder vínculo e papel paroquial, não serem a única forma de criar conta.
