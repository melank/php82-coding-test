<?php

declare(strict_types=1);

/**
 * 予約可否の判定結果を表す Value Object
 */
final readonly class ReservationCheckResult
{
    public function __construct(
        public readonly bool $canReserve,
        public readonly ?DateTimeImmutable $nextAvailableAt
    ) {}
}
