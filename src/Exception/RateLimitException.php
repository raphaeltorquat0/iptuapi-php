<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Limite de requisições excedido.
 */
class RateLimitException extends IPTUAPIException
{
    public function __construct(string $message = 'Limite de requisições excedido')
    {
        parent::__construct($message, 429);
    }
}
