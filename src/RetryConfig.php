<?php

declare(strict_types=1);

namespace IPTUAPI;

/**
 * Configuração de retry para requisições.
 */
class RetryConfig
{
    /**
     * @param int $maxRetries Número máximo de retries (default: 3)
     * @param int $initialDelay Delay inicial em ms (default: 500)
     * @param int $maxDelay Delay máximo em ms (default: 10000)
     * @param float $backoffFactor Fator de backoff exponencial (default: 2.0)
     * @param array $retryableStatuses Status codes que devem ser retried (default: [429, 500, 502, 503, 504])
     */
    public function __construct(
        public readonly int $maxRetries = 3,
        public readonly int $initialDelay = 500,
        public readonly int $maxDelay = 10000,
        public readonly float $backoffFactor = 2.0,
        public readonly array $retryableStatuses = [429, 500, 502, 503, 504],
    ) {
    }
}
