<?php

declare(strict_types=1);

/**
 * 配送先を表す Value Object
 */
final readonly class Destination
{
    public function __construct(
        public string $regionCode
    ) {}
}
