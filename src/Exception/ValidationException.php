<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Erro de validação dos parâmetros.
 */
class ValidationException extends IPTUAPIException
{
    private array $errors;

    public function __construct(
        string $message = 'Parâmetros inválidos',
        array $errors = [],
        ?string $requestId = null
    ) {
        parent::__construct($message, 400, $requestId);
        $this->errors = $errors;
    }

    /**
     * Retorna os erros de validação por campo.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Verifica se há erro em um campo específico.
     */
    public function hasFieldError(string $field): bool
    {
        return isset($this->errors[$field]);
    }

    /**
     * Retorna os erros de um campo específico.
     *
     * @return string[]
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    public function isRetryable(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'errors' => $this->errors,
        ]);
    }
}
