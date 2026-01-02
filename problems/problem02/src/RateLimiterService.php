<?php

declare(strict_types=1);

namespace problems\problem02\src;

use DomainException;

final readonly class RateLimiterService
{
    /**
     * @param LoginAttempt[] $attempts
     */
    public function shouldBlock(array $attempts, RateLimitPolicy $policy): ?BlockReason
    {
        // 1. attempts が空なら null を返す
        if (empty($attempts)) {
            return null;
        }

        // 2. 時系列順かチェック（違反なら例外）
        $prevOccurredAt = null;
        foreach ($attempts as $attempt) {
            if ($prevOccurredAt !== null && $attempt->occurredAt < $prevOccurredAt) {
                throw new DomainException('Attempts must be in chronological order.');
            }
            $prevOccurredAt = $attempt->occurredAt;
        }

        // 3. 最新の試行時刻を基準にウィンドウ開始時刻を算出
        $latestAttempt = $attempts[count($attempts) - 1];
        $windowStart = $latestAttempt->occurredAt->modify("-{$policy->windowMinutes} minutes");

        // 4. ウィンドウ内の試行を走査し、失敗回数をカウント
        $failureCount = 0;
        foreach ($attempts as $attempt) {
            // ウィンドウ外（windowMinutes分より前）はスキップ。境界（ちょうどN分前）は含む。
            if ($attempt->occurredAt < $windowStart) {
                continue;
            }

            if ($attempt->result === AttemptResult::Success) {
                // Success が発生した場合、その時点で失敗回数はリセット
                $failureCount = 0;
            } else {
                $failureCount++;
            }
        }

        // 5. 失敗回数が failureLimit 以上ならブロック
        return $failureCount >= $policy->failureLimit ? BlockReason::TooManyFailures : null;
    }
}
