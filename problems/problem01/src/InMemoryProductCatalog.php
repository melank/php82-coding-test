<?php

declare(strict_types=1);

namespace problems\problem01\src;

final readonly class InMemoryProductCatalog
{
    /**
     * @param array<int, int> $pricesById productId => price
     */
    public function __construct(private array $pricesById) {}

    public function priceOf(int $productId): ?int
    {
        return $this->pricesById[$productId] ?? null;
    }
}
