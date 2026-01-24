<?php

declare(strict_types=1);

namespace IPTUAPI\Tests;

use PHPUnit\Framework\TestCase;
use IPTUAPI\RateLimitInfo;

/**
 * Tests for RateLimitInfo class.
 */
class RateLimitInfoTest extends TestCase
{
    public function testRateLimitInfoCreation(): void
    {
        $info = new RateLimitInfo(
            limit: 1000,
            remaining: 950,
            reset: time() + 3600
        );

        $this->assertEquals(1000, $info->limit);
        $this->assertEquals(950, $info->remaining);
        $this->assertGreaterThan(time(), $info->reset);
    }

    public function testRateLimitInfoWithZeroRemaining(): void
    {
        $info = new RateLimitInfo(
            limit: 100,
            remaining: 0,
            reset: time() + 60
        );

        $this->assertEquals(0, $info->remaining);
    }

    public function testGetResetDateTime(): void
    {
        $reset = time() + 3600;
        $info = new RateLimitInfo(
            limit: 100,
            remaining: 50,
            reset: $reset
        );

        $dateTime = $info->getResetDateTime();
        $this->assertInstanceOf(\DateTimeImmutable::class, $dateTime);
        $this->assertEquals($reset, $dateTime->getTimestamp());
    }

    public function testIsExhaustedByCheckingRemaining(): void
    {
        $info = new RateLimitInfo(
            limit: 100,
            remaining: 0,
            reset: time() + 60
        );

        $this->assertEquals(0, $info->remaining);
    }

    public function testIsNotExhaustedByCheckingRemaining(): void
    {
        $info = new RateLimitInfo(
            limit: 100,
            remaining: 50,
            reset: time() + 60
        );

        $this->assertGreaterThan(0, $info->remaining);
    }

    public function testReadonlyProperties(): void
    {
        $info = new RateLimitInfo(
            limit: 1000,
            remaining: 500,
            reset: time() + 3600
        );

        // Verify readonly properties are accessible
        $this->assertIsInt($info->limit);
        $this->assertIsInt($info->remaining);
        $this->assertIsInt($info->reset);
    }

    public function testUsageCalculation(): void
    {
        $info = new RateLimitInfo(
            limit: 100,
            remaining: 75,
            reset: time() + 3600
        );

        // Calculate usage manually
        $usage = $info->limit - $info->remaining;
        $this->assertEquals(25, $usage);
    }

    public function testUsagePercentageCalculation(): void
    {
        $info = new RateLimitInfo(
            limit: 100,
            remaining: 75,
            reset: time() + 3600
        );

        // Calculate percentage manually
        $percentage = (($info->limit - $info->remaining) / $info->limit) * 100;
        $this->assertEquals(25.0, $percentage);
    }

    public function testAtFullCapacity(): void
    {
        $info = new RateLimitInfo(
            limit: 100,
            remaining: 0,
            reset: time() + 3600
        );

        $used = $info->limit - $info->remaining;
        $this->assertEquals($info->limit, $used);
    }

    public function testNoUsage(): void
    {
        $info = new RateLimitInfo(
            limit: 100,
            remaining: 100,
            reset: time() + 3600
        );

        $used = $info->limit - $info->remaining;
        $this->assertEquals(0, $used);
    }
}
