<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

use Exception;

/**
 * Exceção base para erros da IPTU API.
 */
class IPTUAPIException extends Exception
{
    protected int $statusCode;

    public function __construct(string $message = 'Erro na API', int $statusCode = 0)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
