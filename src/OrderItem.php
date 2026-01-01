<?php

declare(strict_types=1);

final readonly class OrderItem
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
    }
}
