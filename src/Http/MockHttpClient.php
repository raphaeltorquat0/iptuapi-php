<?php

declare(strict_types=1);

namespace IPTUAPI\Http;

use IPTUAPI\Exception\NetworkException;
use IPTUAPI\Exception\TimeoutException;

/**
 * Mock HTTP client for testing.
 * Allows queueing responses and simulating various conditions.
 */
class MockHttpClient implements HttpClientInterface
{
    /** @var array<HttpResponse|\Throwable> */
    private array $queue = [];

    /** @var array<array{method: string, url: string, headers: array, body: ?string, timeout: int}> */
    private array $history = [];

    /**
     * Queue a response to be returned on next request.
     */
    public function addResponse(HttpResponse $response): self
    {
        $this->queue[] = $response;
        return $this;
    }

    /**
     * Queue multiple responses.
     *
     * @param HttpResponse[] $responses
     */
    public function addResponses(array $responses): self
    {
        foreach ($responses as $response) {
            $this->addResponse($response);
        }
        return $this;
    }

    /**
     * Queue an exception to be thrown on next request.
     */
    public function addException(\Throwable $exception): self
    {
        $this->queue[] = $exception;
        return $this;
    }

    /**
     * Simulate a timeout on next request.
     */
    public function addTimeout(int $seconds = 30): self
    {
        $this->queue[] = new TimeoutException("Timeout após {$seconds}s", $seconds);
        return $this;
    }

    /**
     * Simulate a network error on next request.
     */
    public function addNetworkError(string $message = 'Connection refused'): self
    {
        $this->queue[] = new NetworkException("Erro de conexão: {$message}");
        return $this;
    }

    /**
     * Create a JSON response.
     */
    public static function jsonResponse(
        int $statusCode,
        array $data,
        array $headers = []
    ): HttpResponse {
        $defaultHeaders = [
            'content-type' => ['application/json'],
        ];
        $headers = array_merge($defaultHeaders, $headers);

        return new HttpResponse(
            $statusCode,
            json_encode($data),
            $headers
        );
    }

    /**
     * Create a success response with rate limit headers.
     */
    public static function successResponse(
        array $data,
        int $limit = 1000,
        int $remaining = 999,
        int $reset = 1704067200,
        string $requestId = 'req_test123'
    ): HttpResponse {
        return self::jsonResponse(200, $data, [
            'x-ratelimit-limit' => [(string) $limit],
            'x-ratelimit-remaining' => [(string) $remaining],
            'x-ratelimit-reset' => [(string) $reset],
            'x-request-id' => [$requestId],
        ]);
    }

    /**
     * Create an error response.
     */
    public static function errorResponse(
        int $statusCode,
        string $detail,
        array $extraData = [],
        array $headers = []
    ): HttpResponse {
        return self::jsonResponse(
            $statusCode,
            array_merge(['detail' => $detail], $extraData),
            $headers
        );
    }

    /**
     * Get request history.
     *
     * @return array<array{method: string, url: string, headers: array, body: ?string, timeout: int}>
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * Get last request from history.
     *
     * @return array{method: string, url: string, headers: array, body: ?string, timeout: int}|null
     */
    public function getLastRequest(): ?array
    {
        return $this->history[count($this->history) - 1] ?? null;
    }

    /**
     * Clear request history.
     */
    public function clearHistory(): self
    {
        $this->history = [];
        return $this;
    }

    /**
     * Clear response queue.
     */
    public function clearQueue(): self
    {
        $this->queue = [];
        return $this;
    }

    /**
     * Reset both history and queue.
     */
    public function reset(): self
    {
        return $this->clearHistory()->clearQueue();
    }

    /**
     * Assert that a specific number of requests were made.
     */
    public function assertRequestCount(int $expected): void
    {
        $actual = count($this->history);
        if ($actual !== $expected) {
            throw new \AssertionError(
                "Expected {$expected} requests, but {$actual} were made."
            );
        }
    }

    /**
     * Assert that no requests were made.
     */
    public function assertNoRequests(): void
    {
        $this->assertRequestCount(0);
    }

    public function request(
        string $method,
        string $url,
        array $headers,
        ?string $body,
        int $timeout
    ): HttpResponse {
        // Record request in history
        $this->history[] = [
            'method' => $method,
            'url' => $url,
            'headers' => $headers,
            'body' => $body,
            'timeout' => $timeout,
        ];

        // Get next queued response/exception
        if (empty($this->queue)) {
            throw new \RuntimeException(
                "MockHttpClient: No responses in queue for request: {$method} {$url}"
            );
        }

        $item = array_shift($this->queue);

        if ($item instanceof \Throwable) {
            throw $item;
        }

        return $item;
    }
}
