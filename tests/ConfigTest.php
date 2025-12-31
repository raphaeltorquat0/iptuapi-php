<?php

declare(strict_types=1);

namespace IPTUAPI\Tests;

use PHPUnit\Framework\TestCase;
use IPTUAPI\IPTUClient;
use IPTUAPI\ClientConfig;
use IPTUAPI\RetryConfig;
use IPTUAPI\RateLimitInfo;

class ConfigTest extends TestCase
{
    public function testClientConfigDefaults(): void
    {
        $config = new ClientConfig();

        $this->assertEquals('https://iptuapi.com.br/api/v1', $config->baseUrl);
        $this->assertEquals(30, $config->timeout);
        $this->assertNull($config->logger);
        $this->assertNull($config->retryConfig);
    }

    public function testClientConfigCustomValues(): void
    {
        $retryConfig = new RetryConfig(maxRetries: 5);
        $config = new ClientConfig(
            baseUrl: 'https://custom.api.com',
            timeout: 60,
            retryConfig: $retryConfig
        );

        $this->assertEquals('https://custom.api.com', $config->baseUrl);
        $this->assertEquals(60, $config->timeout);
        $this->assertEquals(5, $config->retryConfig->maxRetries);
    }

    public function testIPTUClientWithConfig(): void
    {
        $config = new ClientConfig(
            baseUrl: 'https://custom.api.com',
            timeout: 60
        );
        $client = new IPTUClient('test-api-key', $config);

        $this->assertInstanceOf(IPTUClient::class, $client);
    }

    public function testRetryConfigDefaults(): void
    {
        $config = new RetryConfig();

        $this->assertEquals(3, $config->maxRetries);
        $this->assertEquals(500, $config->initialDelay);
        $this->assertEquals(10000, $config->maxDelay);
        $this->assertEquals(2.0, $config->backoffFactor);
        $this->assertEquals([429, 500, 502, 503, 504], $config->retryableStatuses);
    }

    public function testRetryConfigCustomValues(): void
    {
        $config = new RetryConfig(
            maxRetries: 5,
            initialDelay: 1000,
            maxDelay: 30000,
            backoffFactor: 3.0,
            retryableStatuses: [429, 503]
        );

        $this->assertEquals(5, $config->maxRetries);
        $this->assertEquals(1000, $config->initialDelay);
        $this->assertEquals(30000, $config->maxDelay);
        $this->assertEquals(3.0, $config->backoffFactor);
        $this->assertEquals([429, 503], $config->retryableStatuses);
    }

    public function testRateLimitInfo(): void
    {
        $timestamp = time() + 3600;
        $info = new RateLimitInfo(
            limit: 1000,
            remaining: 500,
            reset: $timestamp
        );

        $this->assertEquals(1000, $info->limit);
        $this->assertEquals(500, $info->remaining);
        $this->assertEquals($timestamp, $info->reset);
    }

    public function testRateLimitInfoGetResetDateTime(): void
    {
        $timestamp = time() + 3600;
        $info = new RateLimitInfo(1000, 500, $timestamp);

        $dateTime = $info->getResetDateTime();

        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->assertEquals($timestamp, $dateTime->getTimestamp());
    }

    public function testRateLimitInfoImmutability(): void
    {
        $info = new RateLimitInfo(1000, 500, time());

        // Readonly properties should not be modifiable
        $this->assertEquals(1000, $info->limit);
        $this->assertEquals(500, $info->remaining);
    }
}
