<?php

declare(strict_types=1);

readonly class DailyReport
{
    /**
     * @param array<string, int> $categoryBreakdown
     */
    public function __construct(
        public string $date,
        public int $orderCount,
        public int $grossSales,
        public int $refunds,
        public int $netSales,
        public array $categoryBreakdown,
    ) {
    }
}
