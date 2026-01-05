<?php

declare(strict_types=1);

/**
 * 送料計算サービス
 */
final readonly class ShippingService
{
    private const OKINAWA_REGION_CODE = 'JP-OKINAWA';

    /**
     * 送料を計算する。
     *
     * 計算順序:
     * 1. cart の商品合計金額 subtotal を計算
     * 2. destination の base fee を取得
     * 3. shipping method の multiplier を適用 → feeBase
     * 4. 送料無料判定（subtotal >= threshold かつ region != OKINAWA）
     * 5. hazmat 商品が含まれれば hazmatFee を加算
     * 6. 最終送料を int で返す（端数は切り上げ）
     *
     * @throws DomainException 未知の regionCode または ShippingMethod の場合
     */
    public function calculate(
        Cart $cart,
        Destination $dest,
        ShippingMethod $method,
        PricingPolicy $policy
    ): int {
        // 1. subtotal を計算
        $subtotal = $cart->subtotal();

        // 2. destination の base fee を取得
        if (!array_key_exists($dest->regionCode, $policy->baseFees)) {
            throw new DomainException("Unknown region code: {$dest->regionCode}");
        }
        $baseFee = $policy->baseFees[$dest->regionCode];

        // 3. shipping method の multiplier を取得・適用
        if (!array_key_exists($method->value, $policy->multipliers)) {
            throw new DomainException("Unknown shipping method: {$method->value}");
        }
        $multiplier = $policy->multipliers[$method->value];
        $feeBase = (int) ceil($baseFee * $multiplier);

        // 4. 送料無料判定（沖縄は対象外）
        $isFreeShipping = $subtotal >= $policy->freeShippingThreshold
                       && $dest->regionCode !== self::OKINAWA_REGION_CODE;
        if ($isFreeShipping) {
            $feeBase = 0;
        }

        // 5. hazmat 商品が含まれれば hazmatFee を加算
        if ($cart->hasHazmat()) {
            $feeBase += $policy->hazmatFee;
        }

        // 6. 最終送料を返す
        return $feeBase;
    }
}
