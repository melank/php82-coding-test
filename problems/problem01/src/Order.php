<?php

declare(strict_types=1);

namespace problems\problem01\src;

final class Order
{
    /**
     * @param list<OrderItem> $items
     * @param array<int, int> $pricesByProductId
     */
    private function __construct(
        public readonly int $userId,
        public readonly OrderStatus $status,
        private array $items,
        private array $pricesByProductId,
    ) {}

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
        return array_sum(array_map(fn(OrderItem $item) => $item->quantity, $this->items));
    }

    public function totalPrice(): int
    {
        return array_sum(array_map(
            fn(OrderItem $item) => $this->pricesByProductId[$item->productId] * $item->quantity,
            $this->items
        ));
    }
}
