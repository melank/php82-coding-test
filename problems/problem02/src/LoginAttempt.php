<?php

declare(strict_types=1);

namespace problems\problem02\src;

use DateTimeImmutable;
final readonly class LoginAttempt {

    public function __construct(
        public readonly int $userId,
        public readonly AttemptResult $result,
        public readonly DateTimeImmutable $occurredAt
    ) {}
};