<?php

declare(strict_types=1);

/**
 * 予約の重複チェックを行うサービス
 */
final readonly class ReservationService
{
    /**
     * 新しい予約リクエストが受け入れ可能かを判定する。
     *
     * 判定ルール:
     * - 半開区間 [startAt, endAt) で重複を判定
     * - 重複がある場合、連続する予約をスキップして次の空き時刻を返す
     *
     * @param TimeRange[] $existing 既存予約（startAt 昇順）
     * @param ReservationRequest $request 新規予約リクエスト
     * @return ReservationCheckResult 判定結果
     * @throws InvalidArgumentException 配列要素が TimeRange でない場合
     * @throws DomainException 配列が startAt 昇順でない場合
     */
    public function check(array $existing, ReservationRequest $request): ReservationCheckResult
    {
        // ─────────────────────────────────────────────────────────────
        // 早期リターン: 既存予約がなければ予約可能
        // ─────────────────────────────────────────────────────────────
        if ($existing === []) {
            return new ReservationCheckResult(canReserve: true, nextAvailableAt: null);
        }

        // ─────────────────────────────────────────────────────────────
        // バリデーション: 型チェック + startAt 昇順チェック
        // ─────────────────────────────────────────────────────────────
        $prevStartAt = null;
        foreach ($existing as $reservation) {
            if (!$reservation instanceof TimeRange) {
                throw new InvalidArgumentException('All reservations must be TimeRange instances.');
            }
            if ($prevStartAt !== null && $reservation->startAt <= $prevStartAt) {
                throw new DomainException('Existing reservations must be sorted by startAt in ascending order.');
            }
            $prevStartAt = $reservation->startAt;
        }

        // ─────────────────────────────────────────────────────────────
        // 重複判定: リクエストと重複する予約を探す
        // ─────────────────────────────────────────────────────────────
        $overlappingEndAt = null;
        foreach ($existing as $reservation) {
            // 半開区間の重複条件: A.startAt < B.endAt && B.startAt < A.endAt
            if ($request->range->startAt < $reservation->endAt &&
                $reservation->startAt < $request->range->endAt) {
                $overlappingEndAt = $reservation->endAt;
            }
        }

        // 重複なし → 予約可能
        if ($overlappingEndAt === null) {
            return new ReservationCheckResult(canReserve: true, nextAvailableAt: null);
        }

        // ─────────────────────────────────────────────────────────────
        // 連続する予約をスキップして次の空き時刻を算出
        // ─────────────────────────────────────────────────────────────
        // 重複した予約の終了時刻から、連続している予約をすべてスキップ
        $nextAvailableAt = $overlappingEndAt;
        foreach ($existing as $reservation) {
            // 連続判定: 現在の nextAvailableAt と予約の startAt が一致
            if ($reservation->startAt === $nextAvailableAt) {
                $nextAvailableAt = $reservation->endAt;
            }
        }

        return new ReservationCheckResult(canReserve: false, nextAvailableAt: $nextAvailableAt);
    }
}
