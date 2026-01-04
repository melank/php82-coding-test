<?php

declare(strict_types=1);

final readonly class ReservationCheckResult {

    public function __construct(
        public readonly bool $canReserve,
        public readonly ?DateTimeImmutable $nextAvailableAt
    ) {}
}
