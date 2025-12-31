<?php

declare(strict_types=1);

namespace IPTUAPI\Exception;

/**
 * Acesso negado (plano não autorizado).
 */
class ForbiddenException extends IPTUAPIException
{
    private ?string $requiredPlan;

    public function __construct(
        string $message = 'Plano não autorizado para este recurso',
        ?string $requiredPlan = null,
        ?string $requestId = null
    ) {
        parent::__construct($message, 403, $requestId);
        $this->requiredPlan = $requiredPlan;
    }

    public function getRequiredPlan(): ?string
    {
        return $this->requiredPlan;
    }

    public function isRetryable(): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'required_plan' => $this->requiredPlan,
        ]);
    }
}
