<?php

declare(strict_types=1);
final class ReservationService {

    public function __construct() {}

    public function check(array $existing, ReservationRequest $request): ReservationCheckResult
    {
        if ($existing === []) {
            return new ReservationCheckResult(true, null);
        }

        $prev = null;
        $nextIdleTime = null;
        // startAtで昇順に並んでいることを確認
        // 並んでいなければDomainExceptionを投げる
        foreach ($existing as $e) {
            if ($prev !== null && $prev->startAt >= $e->startAt) {
                throw new DomainException();
            }
            // リクエスト時刻が既存の申込みと重複している
            if ($request->range->endAt > $e->startAt && $e->endAt > $request->range->startAt) {
                $nextIdleTime = $e->endAt;
            }
            $prev = $e;
        }

        // 重複した申込みがない
        if ($nextIdleTime === null) {
            return new ReservationCheckResult(true, null);
        }

        return new ReservationCheckResult(false, $nextIdleTime);
    }
}