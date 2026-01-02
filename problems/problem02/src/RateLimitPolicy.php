<?php

declare(strict_types=1);

namespace problems\problem02\src;

use DomainException;

final readonly class RateLimitPolicy {

    public function __construct(
        public readonly int $windowMinutes,
        public readonly int $failureLimit,
    ) {
        if ($this->windowMinutes <= 0 || $this->failureLimit <= 0) {
            throw new DomainException();
        }
    }
}