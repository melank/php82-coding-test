<?php

declare(strict_types=1);

namespace problems\problem01\src;

final readonly class OrderItem
{
    public function __construct(
        public readonly int $totalQuantity,
        public readonly int $totalPrice)
    {}
}
