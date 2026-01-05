<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SalesReportServiceTest extends TestCase
{
    // ========================================
    // 正常系
    // ========================================

    #[Test]
    public function 単日Paidのみの場合grossとnetが等しくrefundsは0になる(): void
    {
        // 注文1: subtotal = 100*2 + 200*1 = 400, total = 400 - 50 + 100 = 450
        // 注文2: subtotal = 300*3 = 900, total = 900 - 0 + 0 = 900
        // grossSales = 450 + 900 = 1350
        // refunds = 0
        // netSales = 1350
        // orderCount = 2
        // categoryBreakdown: food = 100*2 = 200, book = 200*1 + 300*3 = 1100
        $orders = [
            new OrderRecord(
                orderId: 1,
                occurredAt: new DateTimeImmutable('2024-01-15 10:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 1, unitPrice: 100, quantity: 2, category: 'food'),
                    new OrderItemRecord(productId: 2, unitPrice: 200, quantity: 1, category: 'book'),
                ],
                discount: 50,
                shippingFee: 100,
                status: OrderStatus::Paid,
            ),
            new OrderRecord(
                orderId: 2,
                occurredAt: new DateTimeImmutable('2024-01-15 14:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 3, unitPrice: 300, quantity: 3, category: 'book'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
        ];

        $reports = SalesReportService::buildDailyReports($orders);

        $this->assertCount(1, $reports);
        $this->assertSame('2024-01-15', $reports[0]->date);
        $this->assertSame(2, $reports[0]->orderCount);
        $this->assertSame(1350, $reports[0]->grossSales);
        $this->assertSame(0, $reports[0]->refunds);
        $this->assertSame(1350, $reports[0]->netSales);
        $this->assertSame(['food' => 200, 'book' => 1100], $reports[0]->categoryBreakdown);
    }

    #[Test]
    public function Refundedがある場合refundsが増えnetSalesが減る(): void
    {
        // Paid: subtotal = 500*1 = 500, total = 500 - 0 + 0 = 500
        // Refunded: subtotal = 200*1 = 200, total = 200 - 0 + 0 = 200
        // grossSales = 500
        // refunds = 200
        // netSales = 500 - 200 = 300
        // orderCount = 2 (Paid + Refunded)
        $orders = [
            new OrderRecord(
                orderId: 1,
                occurredAt: new DateTimeImmutable('2024-02-10 09:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 1, unitPrice: 500, quantity: 1, category: 'food'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
            new OrderRecord(
                orderId: 2,
                occurredAt: new DateTimeImmutable('2024-02-10 15:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 2, unitPrice: 200, quantity: 1, category: 'book'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Refunded,
            ),
        ];

        $reports = SalesReportService::buildDailyReports($orders);

        $this->assertCount(1, $reports);
        $this->assertSame('2024-02-10', $reports[0]->date);
        $this->assertSame(2, $reports[0]->orderCount);
        $this->assertSame(500, $reports[0]->grossSales);
        $this->assertSame(200, $reports[0]->refunds);
        $this->assertSame(300, $reports[0]->netSales);
    }

    #[Test]
    public function Cancelledは集計対象外でorderCountにも金額にも影響しない(): void
    {
        // Paid: subtotal = 100*1 = 100, total = 100
        // Cancelled: subtotal = 1000*5 = 5000, total = 5000 (無視される)
        // grossSales = 100
        // refunds = 0
        // netSales = 100
        // orderCount = 1 (Paidのみ)
        $orders = [
            new OrderRecord(
                orderId: 1,
                occurredAt: new DateTimeImmutable('2024-03-01 10:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 1, unitPrice: 100, quantity: 1, category: 'food'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
            new OrderRecord(
                orderId: 2,
                occurredAt: new DateTimeImmutable('2024-03-01 11:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 2, unitPrice: 1000, quantity: 5, category: 'book'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Cancelled,
            ),
        ];

        $reports = SalesReportService::buildDailyReports($orders);

        $this->assertCount(1, $reports);
        $this->assertSame(1, $reports[0]->orderCount);
        $this->assertSame(100, $reports[0]->grossSales);
        $this->assertSame(0, $reports[0]->refunds);
        $this->assertSame(100, $reports[0]->netSales);
        $this->assertSame(['food' => 100], $reports[0]->categoryBreakdown);
    }

    #[Test]
    public function discountでtotalが負になる場合は0にクランプされる(): void
    {
        // subtotal = 100*1 = 100
        // total = 100 - 500 + 0 = -400 → クランプして 0
        // grossSales = 0
        $orders = [
            new OrderRecord(
                orderId: 1,
                occurredAt: new DateTimeImmutable('2024-04-01 10:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 1, unitPrice: 100, quantity: 1, category: 'food'),
                ],
                discount: 500,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
        ];

        $reports = SalesReportService::buildDailyReports($orders);

        $this->assertCount(1, $reports);
        $this->assertSame(0, $reports[0]->grossSales);
        $this->assertSame(0, $reports[0]->netSales);
    }

    #[Test]
    public function 複数日の注文は日付ごとに集計され日付昇順で返される(): void
    {
        // 2024-01-10: subtotal = 100*1 = 100, total = 100
        // 2024-01-12: subtotal = 200*2 = 400, total = 400
        // 2024-01-11: subtotal = 300*1 = 300, total = 300
        // 結果は日付昇順: 2024-01-10, 2024-01-11, 2024-01-12
        $orders = [
            new OrderRecord(
                orderId: 1,
                occurredAt: new DateTimeImmutable('2024-01-10 10:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 1, unitPrice: 100, quantity: 1, category: 'food'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
            new OrderRecord(
                orderId: 2,
                occurredAt: new DateTimeImmutable('2024-01-12 10:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 2, unitPrice: 200, quantity: 2, category: 'book'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
            new OrderRecord(
                orderId: 3,
                occurredAt: new DateTimeImmutable('2024-01-11 10:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 3, unitPrice: 300, quantity: 1, category: 'other'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
        ];

        $reports = SalesReportService::buildDailyReports($orders);

        $this->assertCount(3, $reports);
        $this->assertSame('2024-01-10', $reports[0]->date);
        $this->assertSame('2024-01-11', $reports[1]->date);
        $this->assertSame('2024-01-12', $reports[2]->date);
        $this->assertSame(100, $reports[0]->grossSales);
        $this->assertSame(300, $reports[1]->grossSales);
        $this->assertSame(400, $reports[2]->grossSales);
    }

    #[Test]
    public function categoryBreakdownはPaidのみで集計されRefundedは反映しない(): void
    {
        // Paid: food = 100*2 = 200
        // Refunded: book = 500*1 = 500 (categoryBreakdownには含めない)
        $orders = [
            new OrderRecord(
                orderId: 1,
                occurredAt: new DateTimeImmutable('2024-05-01 10:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 1, unitPrice: 100, quantity: 2, category: 'food'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
            new OrderRecord(
                orderId: 2,
                occurredAt: new DateTimeImmutable('2024-05-01 11:00:00'),
                currency: 'JPY',
                items: [
                    new OrderItemRecord(productId: 2, unitPrice: 500, quantity: 1, category: 'book'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Refunded,
            ),
        ];

        $reports = SalesReportService::buildDailyReports($orders);

        $this->assertCount(1, $reports);
        $this->assertSame(['food' => 200], $reports[0]->categoryBreakdown);
    }

    // ========================================
    // 異常系
    // ========================================

    #[Test]
    public function currencyがJPY以外の場合DomainExceptionをスローする(): void
    {
        $orders = [
            new OrderRecord(
                orderId: 1,
                occurredAt: new DateTimeImmutable('2024-06-01 10:00:00'),
                currency: 'USD',
                items: [
                    new OrderItemRecord(productId: 1, unitPrice: 100, quantity: 1, category: 'food'),
                ],
                discount: null,
                shippingFee: 0,
                status: OrderStatus::Paid,
            ),
        ];

        $this->expectException(DomainException::class);
        SalesReportService::buildDailyReports($orders);
    }

    #[Test]
    public function unitPriceが0以下の場合DomainExceptionをスローする(): void
    {
        $this->expectException(DomainException::class);

        new OrderItemRecord(
            productId: 1,
            unitPrice: 0,
            quantity: 1,
            category: 'food',
        );
    }

    #[Test]
    public function unitPriceが負の場合DomainExceptionをスローする(): void
    {
        $this->expectException(DomainException::class);

        new OrderItemRecord(
            productId: 1,
            unitPrice: -100,
            quantity: 1,
            category: 'food',
        );
    }

    #[Test]
    public function quantityが0以下の場合DomainExceptionをスローする(): void
    {
        $this->expectException(DomainException::class);

        new OrderItemRecord(
            productId: 1,
            unitPrice: 100,
            quantity: 0,
            category: 'food',
        );
    }

    #[Test]
    public function quantityが負の場合DomainExceptionをスローする(): void
    {
        $this->expectException(DomainException::class);

        new OrderItemRecord(
            productId: 1,
            unitPrice: 100,
            quantity: -1,
            category: 'food',
        );
    }

    #[Test]
    public function discountが負の場合DomainExceptionをスローする(): void
    {
        $this->expectException(DomainException::class);

        new OrderRecord(
            orderId: 1,
            occurredAt: new DateTimeImmutable('2024-06-01 10:00:00'),
            currency: 'JPY',
            items: [
                new OrderItemRecord(productId: 1, unitPrice: 100, quantity: 1, category: 'food'),
            ],
            discount: -100,
            shippingFee: 0,
            status: OrderStatus::Paid,
        );
    }

    #[Test]
    public function shippingFeeが負の場合DomainExceptionをスローする(): void
    {
        $this->expectException(DomainException::class);

        new OrderRecord(
            orderId: 1,
            occurredAt: new DateTimeImmutable('2024-06-01 10:00:00'),
            currency: 'JPY',
            items: [
                new OrderItemRecord(productId: 1, unitPrice: 100, quantity: 1, category: 'food'),
            ],
            discount: null,
            shippingFee: -50,
            status: OrderStatus::Paid,
        );
    }
}
