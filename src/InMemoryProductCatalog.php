<?php

declare(strict_types=1);

final class InMemoryProductCatalog
{
    /**
     * @param array<int, int> $pricesById
     */
    public function __construct(
        private readonly array $pricesById,
    ) {
    }

    public function priceOf(int $productId): ?int
    {
        return $this->pricesById[$productId] ?? null;
    }
}
