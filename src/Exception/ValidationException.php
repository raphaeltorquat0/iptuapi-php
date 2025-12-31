<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Erro de validação dos parâmetros.
 */
class ValidationException extends IPTUAPIException
{
    public function __construct(string $message = 'Parâmetros inválidos')
    {
        parent::__construct($message, 400);
    }
}
