<?php

declare(strict_types=1);

namespace problems\problem02\tests;

use DomainException;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use problems\problem02\src\RateLimiterService;
use problems\problem02\src\LoginAttempt;
use problems\problem02\src\AttemptResult;
use problems\problem02\src\RateLimitPolicy;
use problems\problem02\src\BlockReason;

final class RateLimiterServiceTest extends TestCase
{
    private RateLimiterService $service;

    protected function setUp(): void
    {
        $this->service = new RateLimiterService();
    }

    #[Test]
    public function 空の試行履歴の場合はブロックしない(): void
    {
        $policy = new RateLimitPolicy(windowMinutes: 5, failureLimit: 3);

        $result = $this->service->shouldBlock([], $policy);

        $this->assertNull($result);
    }

    #[Test]
    public function 失敗回数が制限未満の場合はブロックしない(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $attempts = [
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-2 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now),
        ];
        $policy = new RateLimitPolicy(windowMinutes: 5, failureLimit: 3);

        $result = $this->service->shouldBlock($attempts, $policy);

        $this->assertNull($result);
    }

    #[Test]
    public function 失敗回数が制限以上の場合はブロックする(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $attempts = [
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-2 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-1 minute')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now),
        ];
        $policy = new RateLimitPolicy(windowMinutes: 5, failureLimit: 3);

        $result = $this->service->shouldBlock($attempts, $policy);

        $this->assertSame(BlockReason::TooManyFailures, $result);
    }

    #[Test]
    public function ウィンドウ外の失敗はカウントしない(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $attempts = [
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-10 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-6 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now),
        ];
        $policy = new RateLimitPolicy(windowMinutes: 5, failureLimit: 3);

        $result = $this->service->shouldBlock($attempts, $policy);

        $this->assertNull($result);
    }

    #[Test]
    public function 成功後の失敗のみカウントする(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $attempts = [
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-4 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-3 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Success, occurredAt: $now->modify('-2 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-1 minute')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now),
        ];
        $policy = new RateLimitPolicy(windowMinutes: 5, failureLimit: 3);

        $result = $this->service->shouldBlock($attempts, $policy);

        $this->assertNull($result);
    }

    #[Test]
    public function 成功後でも失敗が制限以上ならブロックする(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $attempts = [
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-4 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Success, occurredAt: $now->modify('-3 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-2 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-1 minute')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now),
        ];
        $policy = new RateLimitPolicy(windowMinutes: 5, failureLimit: 3);

        $result = $this->service->shouldBlock($attempts, $policy);

        $this->assertSame(BlockReason::TooManyFailures, $result);
    }

    #[Test]
    public function windowMinutesが0以下の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new RateLimitPolicy(windowMinutes: 0, failureLimit: 3);
    }

    #[Test]
    public function failureLimitが0以下の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new RateLimitPolicy(windowMinutes: 5, failureLimit: 0);
    }

    #[Test]
    public function 試行履歴が時系列順でない場合は例外が発生する(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $attempts = [
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-1 minute')),
        ];
        $policy = new RateLimitPolicy(windowMinutes: 5, failureLimit: 3);

        $this->expectException(DomainException::class);

        $this->service->shouldBlock($attempts, $policy);
    }

    #[Test]
    public function ウィンドウ境界ちょうどの失敗はカウントする(): void
    {
        $now = new DateTimeImmutable('2024-01-01 12:00:00');
        $attempts = [
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-5 minutes')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now->modify('-1 minute')),
            new LoginAttempt(userId: 1, result: AttemptResult::Failure, occurredAt: $now),
        ];
        $policy = new RateLimitPolicy(windowMinutes: 5, failureLimit: 3);

        $result = $this->service->shouldBlock($attempts, $policy);

        $this->assertSame(BlockReason::TooManyFailures, $result);
    }
}
