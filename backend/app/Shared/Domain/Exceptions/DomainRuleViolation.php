<?php

namespace App\Shared\Domain\Exceptions;

use RuntimeException;

final class DomainRuleViolation extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $httpStatus = 422,
    ) {
        parent::__construct($message);
    }
}
