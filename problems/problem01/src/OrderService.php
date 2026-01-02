<?php

declare(strict_types=1);

namespace problems\problem01\src;

use DomainException;
use problems\problem01\src\Order;
use problems\problem01\src\InMemoryProductCatalog;

final class OrderService
{
    public function __construct(private InMemoryProductCatalog $inMemoryProductCatalog) {}

    public function place(int $userId, array $items): ?Order
    {
        // 指定されたデータのバリデーションを実施
        if (empty($items)) {
            throw new DomainException('Items cannot be empty');
        }

        $catalog = $this->inMemoryProductCatalog->catalog;
        $totalQuantity = 0;
        $totalPrice = 0;

        foreach($items as $item) {
            $quantity = $item['quantity'];
            if ($quantity <= 0) {
                throw new DomainException("Quantity must be greater than 0");
            }
            $productId = $item['productId'];
            if (!key_exists($productId, $catalog)) {
                throw new DomainException('Product not found: 999');
            }
            $totalQuantity += $quantity;
            $totalPrice += $catalog[$productId] * $quantity;
        }

        // Orderインスタンスを作成する
        // 初期ステータスは Pending
        $order = new Order($userId, new OrderItem($totalQuantity, $totalPrice));
        return $order;
    }
}
