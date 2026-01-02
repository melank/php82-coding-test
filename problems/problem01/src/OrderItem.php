<?php

declare(strict_types=1);

namespace problems\problem01\src;

final readonly class OrderItem
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}
}
