<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class OrderServiceTest extends TestCase
{
    private InMemoryProductCatalog $catalog;
    private OrderService $service;

    protected function setUp(): void
    {
        $this->catalog = new InMemoryProductCatalog([
            1 => 100,
            2 => 200,
            3 => 300,
        ]);
        $this->service = new OrderService($this->catalog);
    }

    #[Test]
    public function 注文を作成できる(): void
    {
        $order = $this->service->place(userId: 42, items: [
            ['productId' => 1, 'quantity' => 2],
            ['productId' => 2, 'quantity' => 1],
        ]);

        $this->assertSame(42, $order->userId);
        $this->assertSame(OrderStatus::Pending, $order->status);
    }

    #[Test]
    public function 合計数量を取得できる(): void
    {
        $order = $this->service->place(userId: 1, items: [
            ['productId' => 1, 'quantity' => 2],
            ['productId' => 2, 'quantity' => 3],
        ]);

        $this->assertSame(5, $order->totalQuantity());
    }

    #[Test]
    public function 合計金額を取得できる(): void
    {
        $order = $this->service->place(userId: 1, items: [
            ['productId' => 1, 'quantity' => 2],  // 100 * 2 = 200
            ['productId' => 2, 'quantity' => 1],  // 200 * 1 = 200
        ]);

        $this->assertSame(400, $order->totalPrice());
    }

    #[Test]
    public function 商品が空の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Items cannot be empty');

        $this->service->place(userId: 1, items: []);
    }

    #[Test]
    public function 数量が0の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Quantity must be greater than 0');

        $this->service->place(userId: 1, items: [
            ['productId' => 1, 'quantity' => 0],
        ]);
    }

    #[Test]
    public function 数量が負の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Quantity must be greater than 0');

        $this->service->place(userId: 1, items: [
            ['productId' => 1, 'quantity' => -1],
        ]);
    }

    #[Test]
    public function 存在しない商品の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Product not found: 999');

        $this->service->place(userId: 1, items: [
            ['productId' => 999, 'quantity' => 1],
        ]);
    }
}
