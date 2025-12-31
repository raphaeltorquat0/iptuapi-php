<?php

declare(strict_types=1);

namespace IPTUAPI;

use Psr\Log\LoggerInterface;

/**
 * Configuração do cliente IPTU API.
 */
class ClientConfig
{
    public const DEFAULT_USER_AGENT = 'iptuapi-php/2.0.0';

    public function __construct(
        public readonly string $baseUrl = 'https://iptuapi.com.br/api/v1',
        public readonly int $timeout = 30,
        public readonly ?RetryConfig $retryConfig = null,
        public readonly ?LoggerInterface $logger = null,
        public readonly string $userAgent = self::DEFAULT_USER_AGENT,
    ) {
    }
}
