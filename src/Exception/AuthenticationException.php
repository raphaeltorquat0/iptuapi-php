<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Erro de autenticação (API Key inválida).
 */
class AuthenticationException extends IPTUAPIException
{
    public function __construct(
        string $message = 'API Key inválida ou expirada',
        ?string $requestId = null
    ) {
        parent::__construct($message, 401, $requestId);
    }

    public function isRetryable(): bool
    {
        return false;
    }
}
