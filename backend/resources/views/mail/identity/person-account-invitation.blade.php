<x-mail::message>
# Olá, {{ $personName }}!

Você foi convidado para acessar o Eclesiapp e assumir o perfil que já foi cadastrado para você.

<x-mail::button :url="$acceptanceUrl">
Criar meu acesso
</x-mail::button>

Este convite é pessoal, pode ser usado uma única vez e expira em {{ $expiresAt->format('d/m/Y \à\s H:i') }} UTC.
Se você não esperava este convite, ignore esta mensagem.

Obrigado,<br>
Equipe Eclesiapp
</x-mail::message>
