<?php

declare(strict_types=1);

final readonly class TimeRange {

    public function __construct(
        public readonly DateTimeImmutable $startAt,
        public readonly DateTimeImmutable $endAt
    ) {
        if ($this->startAt >= $this->endAt) {
            throw new DomainException();
        }
    }
}