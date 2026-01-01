<?php

declare(strict_types=1);

final class Order
{
    /**
     * @param list<OrderItem> $items
     * @param array<int, int> $pricesByProductId
     */
    private function __construct(
        public readonly int $userId,
        public readonly OrderStatus $status,
        private readonly array $items,
        private readonly array $pricesByProductId,
    ) {
    }

    /**
     * @param list<OrderItem> $items
     * @param array<int, int> $pricesByProductId
     */
    public static function pending(int $userId, array $items, array $pricesByProductId): self
    {
        return new self($userId, OrderStatus::Pending, $items, $pricesByProductId);
    }

    public function totalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->quantity;
        }
        return $total;
    }

    public function totalPrice(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $this->pricesByProductId[$item->productId] * $item->quantity;
        }
        return $total;
    }
}
