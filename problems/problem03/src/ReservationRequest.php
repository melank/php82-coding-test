<?php

declare(strict_types=1);

final readonly class ReservationRequest {

    public function __construct(
        public readonly TimeRange $range
    ) {}
}