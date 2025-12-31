<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Recurso não encontrado.
 */
class NotFoundException extends IPTUAPIException
{
    public function __construct(string $message = 'Recurso não encontrado')
    {
        parent::__construct($message, 404);
    }
}
