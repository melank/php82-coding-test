<?php

declare(strict_types=1);

readonly class OrderRecord
{
    /**
     * @param list<OrderItemRecord> $items
     */
    public function __construct(
        public int $orderId,
        public DateTimeImmutable $occurredAt,
        public string $currency,
        public array $items,
        public ?int $discount,
        public int $shippingFee,
        public OrderStatus $status,
    ) {
        if ($discount !== null && $discount < 0) {
            throw new DomainException('Discount must not be negative');
        }
        if ($shippingFee < 0) {
            throw new DomainException('Shipping fee must not be negative');
        }
    }

    public function subtotal(): int
    {
        return array_reduce(
            $this->items,
            fn(int $carry, OrderItemRecord $item) => $carry + $item->subtotal(),
            0
        );
    }

    public function total(): int
    {
        $total = $this->subtotal() - ($this->discount ?? 0) + $this->shippingFee;
        return max(0, $total);
    }
}
