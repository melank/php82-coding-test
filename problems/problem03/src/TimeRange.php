<?php

declare(strict_types=1);

/**
 * 時間範囲を表す Value Object（半開区間 [startAt, endAt)）
 */
final readonly class TimeRange
{
    public function __construct(
        public readonly DateTimeImmutable $startAt,
        public readonly DateTimeImmutable $endAt
    ) {
        if ($this->startAt >= $this->endAt) {
            throw new DomainException('startAt must be before endAt.');
        }
    }
}
