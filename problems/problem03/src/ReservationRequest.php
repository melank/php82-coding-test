<?php

declare(strict_types=1);

/**
 * 予約リクエストを表す Value Object
 *
 * 現時点では TimeRange のみを保持するが、
 * 今後以下のようなビジネスルールの追加が想定される:
 * - 予約者情報（userId）
 * - 予約の最小・最大時間の制限
 * - 営業時間内のみ予約可能
 * - 予約可能な曜日の制限
 */
final readonly class ReservationRequest
{
    public function __construct(
        public TimeRange $range
    ) {
        // 将来のバリデーションロジックをここに追加
    }
}
