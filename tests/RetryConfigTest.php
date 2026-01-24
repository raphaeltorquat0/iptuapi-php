<?php

declare(strict_types=1);

namespace IPTUAPI\Tests;

use PHPUnit\Framework\TestCase;
use IPTUAPI\RetryConfig;

/**
 * Tests for RetryConfig class.
 */
class RetryConfigTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new RetryConfig();

        $this->assertEquals(3, $config->maxRetries);
        $this->assertEquals(500, $config->initialDelay);
        $this->assertEquals(10000, $config->maxDelay);
        $this->assertEquals(2.0, $config->backoffFactor);
    }

    public function testCustomValues(): void
    {
        $config = new RetryConfig(
            maxRetries: 5,
            initialDelay: 1000,
            maxDelay: 30000,
            backoffFactor: 2.5
        );

        $this->assertEquals(5, $config->maxRetries);
        $this->assertEquals(1000, $config->initialDelay);
        $this->assertEquals(30000, $config->maxDelay);
        $this->assertEquals(2.5, $config->backoffFactor);
    }

    public function testDefaultRetryableStatuses(): void
    {
        $config = new RetryConfig();

        $this->assertContains(429, $config->retryableStatuses);
        $this->assertContains(500, $config->retryableStatuses);
        $this->assertContains(502, $config->retryableStatuses);
        $this->assertContains(503, $config->retryableStatuses);
        $this->assertContains(504, $config->retryableStatuses);
    }

    public function testCustomRetryableStatuses(): void
    {
        $config = new RetryConfig(
            retryableStatuses: [429, 500]
        );

        $this->assertContains(429, $config->retryableStatuses);
        $this->assertContains(500, $config->retryableStatuses);
        $this->assertNotContains(502, $config->retryableStatuses);
    }

    public function testZeroMaxRetries(): void
    {
        $config = new RetryConfig(maxRetries: 0);

        $this->assertEquals(0, $config->maxRetries);
    }

    public function testReadonlyProperties(): void
    {
        $config = new RetryConfig();

        $this->assertIsInt($config->maxRetries);
        $this->assertIsInt($config->initialDelay);
        $this->assertIsInt($config->maxDelay);
        $this->assertIsFloat($config->backoffFactor);
        $this->assertIsArray($config->retryableStatuses);
    }

    public function testManualDelayCalculation(): void
    {
        $config = new RetryConfig(
            initialDelay: 500,
            maxDelay: 10000,
            backoffFactor: 2.0
        );

        // Attempt 0: 500ms
        $delay0 = (int) ($config->initialDelay * pow($config->backoffFactor, 0));
        $this->assertEquals(500, $delay0);

        // Attempt 1: 500 * 2 = 1000ms
        $delay1 = (int) ($config->initialDelay * pow($config->backoffFactor, 1));
        $this->assertEquals(1000, $delay1);

        // Attempt 2: 500 * 4 = 2000ms
        $delay2 = (int) ($config->initialDelay * pow($config->backoffFactor, 2));
        $this->assertEquals(2000, $delay2);

        // Capped at maxDelay
        $delay5 = (int) ($config->initialDelay * pow($config->backoffFactor, 5));
        $delay5Capped = min($delay5, $config->maxDelay);
        $this->assertEquals(10000, $delay5Capped);
    }

    public function testManualIsRetryableCheck(): void
    {
        $config = new RetryConfig(
            retryableStatuses: [429, 500, 502, 503, 504]
        );

        $this->assertTrue(in_array(429, $config->retryableStatuses, true));
        $this->assertTrue(in_array(500, $config->retryableStatuses, true));
        $this->assertTrue(in_array(502, $config->retryableStatuses, true));
        $this->assertTrue(in_array(503, $config->retryableStatuses, true));
        $this->assertTrue(in_array(504, $config->retryableStatuses, true));

        $this->assertFalse(in_array(400, $config->retryableStatuses, true));
        $this->assertFalse(in_array(401, $config->retryableStatuses, true));
        $this->assertFalse(in_array(403, $config->retryableStatuses, true));
        $this->assertFalse(in_array(404, $config->retryableStatuses, true));
    }

    public function testExponentialBackoffBehavior(): void
    {
        $config = new RetryConfig(
            initialDelay: 100,
            maxDelay: 5000,
            backoffFactor: 2.0
        );

        $delays = [];
        for ($i = 0; $i < 10; $i++) {
            $delay = (int) ($config->initialDelay * pow($config->backoffFactor, $i));
            $delays[] = min($delay, $config->maxDelay);
        }

        // First few delays should increase exponentially
        $this->assertEquals(100, $delays[0]);
        $this->assertEquals(200, $delays[1]);
        $this->assertEquals(400, $delays[2]);
        $this->assertEquals(800, $delays[3]);
        $this->assertEquals(1600, $delays[4]);
        $this->assertEquals(3200, $delays[5]);

        // After reaching maxDelay, all subsequent delays are capped
        $this->assertEquals(5000, $delays[6]);
        $this->assertEquals(5000, $delays[7]);
    }

    public function testAllStatusCodesInDefault(): void
    {
        $config = new RetryConfig();

        $expectedStatuses = [429, 500, 502, 503, 504];
        $this->assertEquals($expectedStatuses, $config->retryableStatuses);
    }
}
