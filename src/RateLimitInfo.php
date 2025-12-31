<?php

declare(strict_types=1);

namespace IPTUAPI;

/**
 * Informações de rate limit da API.
 */
class RateLimitInfo
{
    public function __construct(
        public readonly int $limit,
        public readonly int $remaining,
        public readonly int $reset,
    ) {
    }

    /**
     * Retorna a data/hora de reset do rate limit.
     */
    public function getResetDateTime(): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->setTimestamp($this->reset);
    }
}
