<?php

declare(strict_types=1);

/**
 * 送料計算ポリシーを表す Value Object
 */
final readonly class PricingPolicy
{
    /**
     * @param array<string, int> $baseFees 地域別の基本送料
     * @param array<string, float> $multipliers 配送手段による倍率
     * @param int $freeShippingThreshold 送料無料閾値
     * @param int $hazmatFee 危険物手数料
     */
    public function __construct(
        public array $baseFees,
        public array $multipliers,
        public int $freeShippingThreshold,
        public int $hazmatFee
    ) {
        // baseFees のバリデーション
        foreach ($this->baseFees as $regionCode => $fee) {
            if ($fee < 0) {
                throw new DomainException("Base fee for {$regionCode} must not be negative.");
            }
        }

        // freeShippingThreshold のバリデーション
        if ($this->freeShippingThreshold < 0) {
            throw new DomainException('Free shipping threshold must not be negative.');
        }

        // hazmatFee のバリデーション
        if ($this->hazmatFee < 0) {
            throw new DomainException('Hazmat fee must not be negative.');
        }
    }
}
