<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Limite de requisições excedido.
 */
class RateLimitException extends IPTUAPIException
{
    private ?int $retryAfter;

    public function __construct(
        string $message = 'Limite de requisições excedido',
        ?int $retryAfter = null,
        ?string $requestId = null
    ) {
        parent::__construct($message, 429, $requestId);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public function isRetryable(): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'retry_after' => $this->retryAfter,
        ]);
    }
}
