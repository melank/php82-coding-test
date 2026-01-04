<?php

declare(strict_types=1);

/**
 * 予約リクエストを表す Value Object
 */
final readonly class ReservationRequest
{
    public function __construct(
        public readonly TimeRange $range
    ) {}
}
