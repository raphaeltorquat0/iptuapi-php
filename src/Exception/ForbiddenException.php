<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Acesso negado (plano não autorizado).
 */
class ForbiddenException extends IPTUAPIException
{
    public function __construct(string $message = 'Plano não autorizado para este recurso')
    {
        parent::__construct($message, 403);
    }
}
