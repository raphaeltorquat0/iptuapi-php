<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Erro interno do servidor (HTTP 5xx).
 */
class ServerException extends IPTUAPIException
{
    public function __construct(
        string $message = 'Erro interno do servidor',
        int $code = 500,
        ?string $requestId = null
    ) {
        parent::__construct($message, $code, $requestId);
    }

    public function isRetryable(): bool
    {
        return true;
    }
}
