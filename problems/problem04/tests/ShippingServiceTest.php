<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ShippingServiceTest extends TestCase
{
    private ShippingService $service;
    private PricingPolicy $defaultPolicy;

    protected function setUp(): void
    {
        $this->service = new ShippingService();
        $this->defaultPolicy = new PricingPolicy(
            baseFees: [
                'JP-EAST' => 500,
                'JP-WEST' => 600,
                'JP-OKINAWA' => 1200,
            ],
            multipliers: [
                ShippingMethod::Standard->value => 1.0,
                ShippingMethod::Express->value => 1.5,
            ],
            freeShippingThreshold: 5000,
            hazmatFee: 300,
        );
    }

    // ========================================
    // 正常系テスト
    // ========================================

    #[Test]
    public function 関東へのスタンダード配送で基本送料が計算される(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );

        $this->assertSame(500, $fee);
    }

    #[Test]
    public function 関東へのエクスプレス配送で倍率が適用される(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Express,
            $this->defaultPolicy,
        );

        // 500 * 1.5 = 750
        $this->assertSame(750, $fee);
    }

    #[Test]
    public function 関西へのエクスプレス配送で倍率が適用される(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
        ]);
        $dest = new Destination('JP-WEST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Express,
            $this->defaultPolicy,
        );

        // 600 * 1.5 = 900
        $this->assertSame(900, $fee);
    }

    #[Test]
    public function 合計金額が閾値以上の場合は送料無料になる(): void
    {
        $cart = new Cart([
            new CartItem(price: 2500, quantity: 2), // subtotal = 5000
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );

        $this->assertSame(0, $fee);
    }

    #[Test]
    public function 合計金額が閾値ちょうどの場合は送料無料になる(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 5), // subtotal = 5000
        ]);
        $dest = new Destination('JP-WEST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Express,
            $this->defaultPolicy,
        );

        $this->assertSame(0, $fee);
    }

    #[Test]
    public function 沖縄は合計金額が閾値以上でも送料無料にならない(): void
    {
        $cart = new Cart([
            new CartItem(price: 5000, quantity: 2), // subtotal = 10000
        ]);
        $dest = new Destination('JP-OKINAWA');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );

        // 沖縄は送料無料対象外: 1200 * 1.0 = 1200
        $this->assertSame(1200, $fee);
    }

    #[Test]
    public function 沖縄へのエクスプレス配送で倍率が適用される(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
        ]);
        $dest = new Destination('JP-OKINAWA');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Express,
            $this->defaultPolicy,
        );

        // 1200 * 1.5 = 1800
        $this->assertSame(1800, $fee);
    }

    #[Test]
    public function 危険物が含まれる場合はhazmat手数料が加算される(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
            new CartItem(price: 500, quantity: 1, hazmat: true),
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );

        // 500 + 300(hazmat) = 800
        $this->assertSame(800, $fee);
    }

    #[Test]
    public function 送料無料でも危険物手数料は加算される(): void
    {
        $cart = new Cart([
            new CartItem(price: 5000, quantity: 1), // subtotal = 5000 (送料無料)
            new CartItem(price: 100, quantity: 1, hazmat: true),
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );

        // 送料無料だが hazmat 手数料 300 が加算
        $this->assertSame(300, $fee);
    }

    #[Test]
    public function 沖縄で危険物が含まれる場合は送料とhazmat手数料の両方がかかる(): void
    {
        $cart = new Cart([
            new CartItem(price: 10000, quantity: 1, hazmat: true), // 閾値超え
        ]);
        $dest = new Destination('JP-OKINAWA');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );

        // 沖縄は送料無料対象外: 1200 + 300(hazmat) = 1500
        $this->assertSame(1500, $fee);
    }

    #[Test]
    public function multiplier適用後の端数は切り上げられる(): void
    {
        $policy = new PricingPolicy(
            baseFees: [
                'JP-EAST' => 500,
            ],
            multipliers: [
                ShippingMethod::Standard->value => 1.33,
            ],
            freeShippingThreshold: 10000,
            hazmatFee: 300,
        );

        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $policy,
        );

        // 500 * 1.33 = 665.0 (端数なし)
        $this->assertSame(665, $fee);
    }

    #[Test]
    public function multiplier適用後に端数が出る場合は切り上げられる(): void
    {
        $policy = new PricingPolicy(
            baseFees: [
                'JP-EAST' => 601,
            ],
            multipliers: [
                ShippingMethod::Standard->value => 1.33,
            ],
            freeShippingThreshold: 10000,
            hazmatFee: 300,
        );

        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $policy,
        );

        // 601 * 1.33 = 799.33 → ceil = 800
        $this->assertSame(800, $fee);
    }

    #[Test]
    public function 別の端数ケースで切り上げが正しく動作する(): void
    {
        $policy = new PricingPolicy(
            baseFees: [
                'JP-EAST' => 333,
            ],
            multipliers: [
                ShippingMethod::Standard->value => 1.01,
            ],
            freeShippingThreshold: 10000,
            hazmatFee: 300,
        );

        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $policy,
        );

        // 333 * 1.01 = 336.33 → ceil = 337
        $this->assertSame(337, $fee);
    }

    #[Test]
    public function 複数商品の合計金額が正しく計算される(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 2), // 2000
            new CartItem(price: 500, quantity: 3),  // 1500
            new CartItem(price: 300, quantity: 5),  // 1500
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );

        // subtotal = 5000 → 送料無料
        $this->assertSame(0, $fee);
    }

    #[Test]
    public function 合計金額が閾値未満の場合は送料がかかる(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 4), // subtotal = 4000 (閾値未満)
        ]);
        $dest = new Destination('JP-EAST');

        $fee = $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );

        // 4000 < 5000 なので送料 500
        $this->assertSame(500, $fee);
    }

    // ========================================
    // 異常系テスト
    // ========================================

    #[Test]
    public function 商品価格が0の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new CartItem(price: 0, quantity: 1);
    }

    #[Test]
    public function 商品価格が負の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new CartItem(price: -100, quantity: 1);
    }

    #[Test]
    public function 商品数量が0の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new CartItem(price: 100, quantity: 0);
    }

    #[Test]
    public function 商品数量が負の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new CartItem(price: 100, quantity: -1);
    }

    #[Test]
    public function 未知の地域コードの場合は例外が発生する(): void
    {
        $cart = new Cart([
            new CartItem(price: 1000, quantity: 1),
        ]);
        $dest = new Destination('JP-UNKNOWN');

        $this->expectException(DomainException::class);

        $this->service->calculate(
            $cart,
            $dest,
            ShippingMethod::Standard,
            $this->defaultPolicy,
        );
    }

    #[Test]
    public function policyのbaseFeeが負の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new PricingPolicy(
            baseFees: [
                'JP-EAST' => -100,
            ],
            multipliers: [
                ShippingMethod::Standard->value => 1.0,
            ],
            freeShippingThreshold: 5000,
            hazmatFee: 300,
        );
    }

    #[Test]
    public function policyのfreeShippingThresholdが負の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new PricingPolicy(
            baseFees: [
                'JP-EAST' => 500,
            ],
            multipliers: [
                ShippingMethod::Standard->value => 1.0,
            ],
            freeShippingThreshold: -1,
            hazmatFee: 300,
        );
    }

    #[Test]
    public function policyのhazmatFeeが負の場合は例外が発生する(): void
    {
        $this->expectException(DomainException::class);

        new PricingPolicy(
            baseFees: [
                'JP-EAST' => 500,
            ],
            multipliers: [
                ShippingMethod::Standard->value => 1.0,
            ],
            freeShippingThreshold: 5000,
            hazmatFee: -100,
        );
    }
}
