<?php

declare(strict_types=1);

readonly class OrderItemRecord
{
    public function __construct(
        public int $productId,
        public int $unitPrice,
        public int $quantity,
        public string $category,
    ) {
        if ($unitPrice <= 0) {
            throw new DomainException('Unit price must be positive');
        }
        if ($quantity <= 0) {
            throw new DomainException('Quantity must be positive');
        }
    }

    public function subtotal(): int
    {
        return $this->unitPrice * $this->quantity;
    }
}
