<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Recurso não encontrado.
 */
class NotFoundException extends IPTUAPIException
{
    private ?string $resource;

    public function __construct(
        string $message = 'Recurso não encontrado',
        ?string $resource = null,
        ?string $requestId = null
    ) {
        parent::__construct($message, 404, $requestId);
        $this->resource = $resource;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function isRetryable(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'resource' => $this->resource,
        ]);
    }
}
