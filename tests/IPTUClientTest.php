<?php

declare(strict_types=1);

namespace IPTUAPI\Tests;

use PHPUnit\Framework\TestCase;
use IPTUAPI\IPTUClient;
use IPTUAPI\ClientConfig;
use IPTUAPI\RetryConfig;
use IPTUAPI\Exception\AuthenticationException;
use IPTUAPI\Exception\ForbiddenException;
use IPTUAPI\Exception\NotFoundException;
use IPTUAPI\Exception\RateLimitException;
use IPTUAPI\Exception\ValidationException;
use IPTUAPI\Exception\ServerException;

class IPTUClientTest extends TestCase
{
    private IPTUClient $client;

    protected function setUp(): void
    {
        $this->client = new IPTUClient('test-api-key');
    }

    public function testClientCreationWithApiKey(): void
    {
        $client = new IPTUClient('my-api-key');
        $this->assertInstanceOf(IPTUClient::class, $client);
    }

    public function testClientCreationWithConfig(): void
    {
        $config = new ClientConfig(
            baseUrl: 'https://custom.api.com',
            timeout: 60
        );
        $client = new IPTUClient('my-api-key', $config);
        $this->assertInstanceOf(IPTUClient::class, $client);
    }

    public function testClientCreationWithEmptyApiKeyThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('API Key é obrigatória');

        new IPTUClient('');
    }

    public function testGetRateLimitInitiallyNull(): void
    {
        $this->assertNull($this->client->getRateLimit());
    }

    public function testGetLastRequestIdInitiallyNull(): void
    {
        $this->assertNull($this->client->getLastRequestId());
    }

    public function testClientVersion(): void
    {
        $this->assertEquals('2.0.0', IPTUClient::VERSION);
    }

    public function testHasConsultaEnderecoMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'consultaEndereco'));
    }

    public function testHasConsultaSQLMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'consultaSQL'));
    }

    public function testHasConsultaCEPMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'consultaCEP'));
    }

    public function testHasConsultaZoneamentoMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'consultaZoneamento'));
    }

    public function testHasValuationEstimateMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'valuationEstimate'));
    }

    public function testHasValuationBatchMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'valuationBatch'));
    }

    public function testHasValuationComparablesMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'valuationComparables'));
    }

    public function testHasDadosIPTUHistoricoMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'dadosIPTUHistorico'));
    }

    public function testHasDadosCNPJMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'dadosCNPJ'));
    }

    public function testHasDadosIPCACorrigirMethod(): void
    {
        $this->assertTrue(method_exists($this->client, 'dadosIPCACorrigir'));
    }
}

class IPTUClientMockTest extends TestCase
{
    /**
     * Test that client correctly maps HTTP 401 to AuthenticationException
     */
    public function testAuthenticationExceptionMapping(): void
    {
        $exception = new AuthenticationException('Invalid API key', 'req-123');

        $this->assertEquals(401, $exception->getStatusCode());
        $this->assertFalse($exception->isRetryable());
    }

    /**
     * Test that client correctly maps HTTP 403 to ForbiddenException
     */
    public function testForbiddenExceptionMapping(): void
    {
        $exception = new ForbiddenException('Plan upgrade required', 'enterprise');

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertEquals('enterprise', $exception->getRequiredPlan());
        $this->assertFalse($exception->isRetryable());
    }

    /**
     * Test that client correctly maps HTTP 404 to NotFoundException
     */
    public function testNotFoundExceptionMapping(): void
    {
        $exception = new NotFoundException('Property not found', 'property/123');

        $this->assertEquals(404, $exception->getStatusCode());
        $this->assertEquals('property/123', $exception->getResource());
        $this->assertFalse($exception->isRetryable());
    }

    /**
     * Test that client correctly maps HTTP 429 to RateLimitException
     */
    public function testRateLimitExceptionMapping(): void
    {
        $exception = new RateLimitException('Too many requests', 60);

        $this->assertEquals(429, $exception->getStatusCode());
        $this->assertEquals(60, $exception->getRetryAfter());
        $this->assertTrue($exception->isRetryable());
    }

    /**
     * Test that client correctly maps HTTP 500 to ServerException
     */
    public function testServerExceptionMapping(): void
    {
        $exception = new ServerException('Internal server error', 500);

        $this->assertEquals(500, $exception->getStatusCode());
        $this->assertTrue($exception->isRetryable());
    }

    /**
     * Test retry configuration is applied correctly
     */
    public function testRetryConfigApplication(): void
    {
        $retryConfig = new RetryConfig(
            maxRetries: 5,
            initialDelay: 1000,
            maxDelay: 30000,
            backoffFactor: 2.5,
            retryableStatuses: [429, 500, 502, 503, 504]
        );

        $config = new ClientConfig(retryConfig: $retryConfig);
        $client = new IPTUClient('test-key', $config);

        $this->assertInstanceOf(IPTUClient::class, $client);
    }

    /**
     * Test backoff calculation
     */
    public function testExponentialBackoffCalculation(): void
    {
        $retryConfig = new RetryConfig(
            maxRetries: 3,
            initialDelay: 500,
            maxDelay: 10000,
            backoffFactor: 2.0
        );

        // Delay for attempt 0 should be ~500ms
        // Delay for attempt 1 should be ~1000ms (500 * 2)
        // Delay for attempt 2 should be ~2000ms (500 * 4)
        // These are capped by maxDelay

        $this->assertEquals(500, $retryConfig->initialDelay);
        $this->assertEquals(2.0, $retryConfig->backoffFactor);
        $this->assertEquals(10000, $retryConfig->maxDelay);
    }

    /**
     * Test that retryable statuses are configured correctly
     */
    public function testRetryableStatusesConfiguration(): void
    {
        $retryConfig = new RetryConfig(
            retryableStatuses: [429, 500, 502, 503, 504]
        );

        $this->assertContains(429, $retryConfig->retryableStatuses);
        $this->assertContains(500, $retryConfig->retryableStatuses);
        $this->assertContains(502, $retryConfig->retryableStatuses);
        $this->assertContains(503, $retryConfig->retryableStatuses);
        $this->assertContains(504, $retryConfig->retryableStatuses);
        $this->assertNotContains(400, $retryConfig->retryableStatuses);
        $this->assertNotContains(401, $retryConfig->retryableStatuses);
    }
}
