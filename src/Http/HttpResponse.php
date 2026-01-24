<?php

declare(strict_types=1);

namespace IPTUAPI\Http;

/**
 * Representa uma resposta HTTP.
 */
class HttpResponse
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers
    ) {
    }

    /**
     * Verifica se a resposta foi bem-sucedida (2xx).
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Decodifica o body como JSON.
     *
     * @return array|null
     */
    public function json(): ?array
    {
        $data = json_decode($this->body, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Obtém um header específico.
     *
     * @param string $name Nome do header (case-insensitive)
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name][0] ?? null;
    }
}
