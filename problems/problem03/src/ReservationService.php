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
     * @param TimeRange[] $existing 既存予約（startAt 昇順）
     * @param ReservationRequest $request 新規予約リクエスト
     * @return ReservationCheckResult 判定結果
     * @throws InvalidArgumentException 配列要素が TimeRange でない場合
     * @throws DomainException 配列が startAt 昇順でない場合
     */
    public function check(array $existing, ReservationRequest $request): ReservationCheckResult
    {
        if ($existing === []) {
            return ReservationCheckResult::available();
        }

        $prevStartAt = null;
        $overlappingEndAt = null;

        foreach ($existing as $reservation) {
            // 型チェック
            if (!$reservation instanceof TimeRange) {
                throw new InvalidArgumentException('All reservations must be TimeRange instances.');
            }

            // startAt 昇順チェック
            if ($prevStartAt !== null && $reservation->startAt <= $prevStartAt) {
                throw new DomainException('Existing reservations must be sorted by startAt in ascending order.');
            }
            $prevStartAt = $reservation->startAt;

            // 重複判定
            if ($request->range->overlapsWith($reservation)) {
                $overlappingEndAt = $reservation->endAt;
            }
        }

        if ($overlappingEndAt === null) {
            return ReservationCheckResult::available();
        }

        // 連続する予約をスキップして次の空き時刻を算出
        $nextAvailableAt = $overlappingEndAt;
        foreach ($existing as $reservation) {
            if ($reservation->startAt == $nextAvailableAt) {
                $nextAvailableAt = $reservation->endAt;
            }
        }

        return ReservationCheckResult::unavailable($nextAvailableAt);
    }
}
