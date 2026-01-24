<?php

declare(strict_types=1);

namespace IPTUAPI\Http;

/**
 * Interface para abstração do cliente HTTP.
 * Permite injeção de dependência e mocking em testes.
 */
interface HttpClientInterface
{
    /**
     * Executa uma requisição HTTP.
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $url Full URL to request
     * @param array $headers Request headers
     * @param string|null $body Request body (JSON string)
     * @param int $timeout Timeout in seconds
     * @return HttpResponse
     */
    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout
    ): HttpResponse;
}
