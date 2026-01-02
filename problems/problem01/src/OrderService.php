<?php

declare(strict_types=1);

namespace problems\problem01\src;

use DomainException;

final readonly class OrderService
{
    public function __construct(private InMemoryProductCatalog $products) {}

    public function place(int $userId, array $items): Order
    {
        if (empty($items)) {
            throw new DomainException('Items cannot be empty');
        }

        $orderItems = [];
        $pricesByProductId = [];

        foreach ($items as $item) {
            $productId = $item['productId'];
            $quantity = $item['quantity'];

            if ($quantity <= 0) {
                throw new DomainException('Quantity must be greater than 0');
            }

            $price = $this->products->priceOf($productId);
            if ($price === null) {
                throw new DomainException("Product not found: {$productId}");
            }

            $orderItems[] = new OrderItem($productId, $quantity);
            $pricesByProductId[$productId] = $price;
        }

        return Order::pending($userId, $orderItems, $pricesByProductId);
    }
}
