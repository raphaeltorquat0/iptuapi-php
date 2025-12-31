<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Erro de rede/conexão.
 */
class NetworkException extends IPTUAPIException
{
    public function __construct(
        string $message = 'Erro de conexão com a API'
    ) {
        parent::__construct($message);
    }

    public function isRetryable(): bool
    {
        return true;
    }
}
