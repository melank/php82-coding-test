<?php

declare(strict_types=1);

/**
 * 時間範囲を表す Value Object（半開区間 [startAt, endAt)）
 */
final readonly class TimeRange
{
    public function __construct(
        public DateTimeImmutable $startAt,
        public DateTimeImmutable $endAt
    ) {
        if ($this->startAt >= $this->endAt) {
            throw new DomainException('startAt must be before endAt.');
        }
    }

    /**
     * 他の TimeRange と重複するかを判定する（半開区間）
     */
    public function overlapsWith(self $other): bool
    {
        return $this->startAt < $other->endAt && $other->startAt < $this->endAt;
    }

    /**
     * 他の TimeRange と連続しているか（この区間の終了時刻 = 他の開始時刻）
     */
    public function isFollowedBy(self $other): bool
    {
        return $this->endAt == $other->startAt;
    }
}
