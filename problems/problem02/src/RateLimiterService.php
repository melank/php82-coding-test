<?php

declare(strict_types=1);

namespace problems\problem02\src;

use DomainException;
use InvalidArgumentException;

final readonly class RateLimiterService
{
    /**
     * ログイン試行履歴から「今ブロックすべきか」を判定する。
     *
     * 判定ルール:
     * - 最新の試行時刻から直近 windowMinutes 分間を対象とする
     * - その範囲内で failureLimit 回以上の失敗があればブロック
     * - Success が発生すると、それ以前の失敗回数はリセットされる
     *
     * @param LoginAttempt[] $attempts 時系列順（古い → 新しい）のログイン試行履歴
     * @param RateLimitPolicy $policy レート制限ポリシー
     * @return BlockReason|null ブロック理由。ブロック不要の場合は null
     * @throws InvalidArgumentException 配列要素が LoginAttempt でない場合
     * @throws DomainException 配列が時系列順でない場合
     */
    public function shouldBlock(array $attempts, RateLimitPolicy $policy): ?BlockReason
    {
        // 早期リターン: 試行履歴がなければブロック理由もない
        if (empty($attempts)) {
            return null;
        }

        // ─────────────────────────────────────────────────────────────
        // バリデーション: 型チェック + 時系列順チェック
        // ─────────────────────────────────────────────────────────────
        // 配列を走査しながら以下を検証する:
        // - 各要素が LoginAttempt のインスタンスであること
        // - 各要素の occurredAt が直前の要素以上であること（時系列順）
        $prevOccurredAt = null;
        foreach ($attempts as $attempt) {
            if (!$attempt instanceof LoginAttempt) {
                throw new InvalidArgumentException('All attempts must be LoginAttempt instances.');
            }
            if ($prevOccurredAt !== null && $attempt->occurredAt < $prevOccurredAt) {
                throw new DomainException('Attempts must be in chronological order.');
            }
            $prevOccurredAt = $attempt->occurredAt;
        }

        // ─────────────────────────────────────────────────────────────
        // ウィンドウ開始時刻の算出
        // ─────────────────────────────────────────────────────────────
        // 最新の試行時刻を基準に、windowMinutes 分前の時刻を求める。
        // この時刻以降（境界含む）の試行のみが判定対象となる。
        $latestAttempt = $attempts[count($attempts) - 1];
        $windowStart = $latestAttempt->occurredAt->modify("-{$policy->windowMinutes} minutes");

        // ─────────────────────────────────────────────────────────────
        // 失敗回数のカウント（ウィンドウ内のみ、成功でリセット）
        // ─────────────────────────────────────────────────────────────
        // 処理の流れ:
        // 1. ウィンドウ外の試行はスキップ（フィルタリング相当）
        // 2. Success が出現したらカウントを 0 にリセット
        // 3. Failure が出現したらカウントを +1
        // 結果として「最後の Success 以降の連続失敗回数」が得られる
        $failureCount = 0;
        foreach ($attempts as $attempt) {
            // フィルタリング: ウィンドウ外はスキップ（境界は含む）
            if ($attempt->occurredAt < $windowStart) {
                continue;
            }

            // 畳み込み: Success でリセット、Failure でインクリメント
            if ($attempt->result === AttemptResult::Success) {
                $failureCount = 0;
            } else {
                $failureCount++;
            }
        }

        // ─────────────────────────────────────────────────────────────
        // ブロック判定
        // ─────────────────────────────────────────────────────────────
        return $failureCount >= $policy->failureLimit ? BlockReason::TooManyFailures : null;
    }
}
