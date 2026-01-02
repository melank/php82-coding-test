<?php

declare(strict_types=1);

namespace problems\problem01\src;

use problems\problem01\src\OrderStatus;

final class Order
{
    public readonly int $userId;    // ユーザーID
    private readonly OrderItem $item;  // OrderItemの配列
    public readonly OrderStatus $status;    // 注文の状態

    public function __construct(int $userId, OrderItem $item) {
        $this->userId = $userId;
        $this->item = $item;
        $this->status = OrderStatus::Pending;
    }

    public function totalQuantity(): int
    {
        return $this->item->totalQuantity;
    }

    public function totalPrice(): int
    {
        return $this->item->totalPrice;
    }
}
