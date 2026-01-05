<?php

declare(strict_types=1);

/**
 * カート内の商品を表す Value Object
 */
final readonly class CartItem
{
    public function __construct(
        public int $price,
        public int $quantity,
        public bool $hazmat = false
    ) {
        if ($this->price <= 0) {
            throw new DomainException('Price must be greater than 0.');
        }
        if ($this->quantity <= 0) {
            throw new DomainException('Quantity must be greater than 0.');
        }
    }
}
