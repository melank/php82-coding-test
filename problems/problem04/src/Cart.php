<?php

declare(strict_types=1);

/**
 * カートを表す Value Object
 */
final readonly class Cart
{
    /**
     * @param CartItem[] $items
     */
    public function __construct(
        public array $items
    ) {}

    /**
     * 全商品の合計金額を計算する
     */
    public function subtotal(): int
    {
        return array_reduce(
            $this->items,
            fn(int $sum, CartItem $item): int =>
                $sum + ($item->price * $item->quantity),
            0
        );
    }

    /**
     * 危険物が含まれているかを判定する
     */
    public function hasHazmat(): bool
    {
        foreach ($this->items as $item) {
            if ($item->hazmat) {
                return true;
            }
        }
        return false;
    }
}
