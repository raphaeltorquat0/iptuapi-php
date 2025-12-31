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
    protected ?string $requestId;

    public function __construct(
        string $message = 'Erro na API',
        int $statusCode = 0,
        ?string $requestId = null
    ) {
        $this->statusCode = $statusCode;
        $this->requestId = $requestId;
        parent::__construct($message, $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Indica se a operação pode ser retentada.
     */
    public function isRetryable(): bool
    {
        return false;
    }

    /**
     * Retorna uma representação em array da exceção.
     */
    public function toArray(): array
    {
        return [
            'type' => static::class,
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
            'request_id' => $this->requestId,
            'retryable' => $this->isRetryable(),
        ];
    }
}
