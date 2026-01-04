<?php

declare(strict_types=1);

/**
 * 予約可否の判定結果を表す Value Object
 */
final readonly class ReservationCheckResult
{
    private function __construct(
        public bool $canReserve,
        public ?DateTimeImmutable $nextAvailableAt
    ) {}

    /**
     * 予約可能な結果を生成
     */
    public static function available(): self
    {
        return new self(canReserve: true, nextAvailableAt: null);
    }

    /**
     * 予約不可の結果を生成（次に予約可能な時刻を指定）
     */
    public static function unavailable(DateTimeImmutable $nextAvailableAt): self
    {
        return new self(canReserve: false, nextAvailableAt: $nextAvailableAt);
    }
}
