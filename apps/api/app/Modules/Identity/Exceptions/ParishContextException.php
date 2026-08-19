<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use RuntimeException;

final class ParishContextException extends RuntimeException
{
    private function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function required(): self
    {
        return new self(
            'PARISH_CONTEXT_REQUIRED',
            409,
            'Selecione uma paróquia ativa para continuar.',
        );
    }

    public static function accessDenied(): self
    {
        return new self(
            'PARISH_ACCESS_DENIED',
            403,
            'A conta não possui vínculo ativo com a paróquia informada.',
        );
    }
}
