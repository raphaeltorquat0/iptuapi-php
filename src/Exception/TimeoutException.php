<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Timeout na requisição.
 */
class TimeoutException extends IPTUAPIException
{
    private int $timeoutSeconds;

    public function __construct(
        string $message = 'Timeout na requisição',
        int $timeoutSeconds = 30
    ) {
        parent::__construct($message, 408);
        $this->timeoutSeconds = $timeoutSeconds;
    }

    public function getTimeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function isRetryable(): bool
    {
        return true;
    }
}
