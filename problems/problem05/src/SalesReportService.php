<?php

declare(strict_types=1);

class SalesReportService
{
    /**
     * @param list<OrderRecord> $orders
     * @return list<DailyReport> 日付昇順
     * @throws DomainException
     */
    public static function buildDailyReports(array $orders): array
    {
        // 通貨バリデーション
        foreach ($orders as $order) {
            if ($order->currency !== 'JPY') {
                throw new DomainException("Unsupported currency: {$order->currency}");
            }
        }

        // Cancelled を除外
        $targetOrders = array_filter(
            $orders,
            fn(OrderRecord $order) => $order->status !== OrderStatus::Cancelled
        );

        // ============================================================
        // 日付ごとにグルーピングして集計
        // ============================================================
        // array_reduce は配列を1つの値に「畳み込む」関数
        // - 第1引数: 処理する配列 ($targetOrders)
        // - 第2引数: コールバック関数
        //   - $carry: 累積値（前回の処理結果。初回は第3引数の値）
        //   - $order: 現在処理中の要素
        // - 第3引数: 初期値（ここでは空配列 []）
        // ============================================================
        /** @var array<string, array{orderCount: int, grossSales: int, refunds: int, categoryBreakdown: array<string, int>}> $dailyData */
        $dailyData = array_reduce(
            $targetOrders,
            function (array $carry, OrderRecord $order): array {
                // 注文日時から日付文字列（YYYY-MM-DD）を取得
                $date = $order->occurredAt->format('Y-m-d');

                // この日付のデータがまだ $carry に存在しない場合は初期化
                // （新しい日付が出現するたびに実行される）
                if (!isset($carry[$date])) {
                    $carry[$date] = [
                        'orderCount' => 0,
                        'grossSales' => 0,
                        'refunds' => 0,
                        'categoryBreakdown' => [],
                    ];
                }

                // 注文件数をカウント（Paid + Refunded）
                $carry[$date]['orderCount']++;

                // 注文の合計金額を計算（subtotal - discount + shippingFee、0未満は0にクランプ）
                $total = $order->total();

                // ステータスに応じて売上 or 返金に振り分け
                if ($order->status === OrderStatus::Paid) {
                    // Paid: 売上として加算
                    $carry[$date]['grossSales'] += $total;

                    // カテゴリ別集計（Paidのみが対象）
                    foreach ($order->items as $item) {
                        $category = $item->category;
                        // このカテゴリがまだ存在しない場合は初期化
                        if (!isset($carry[$date]['categoryBreakdown'][$category])) {
                            $carry[$date]['categoryBreakdown'][$category] = 0;
                        }
                        // 商品の小計（unitPrice * quantity）を加算
                        $carry[$date]['categoryBreakdown'][$category] += $item->subtotal();
                    }
                } elseif ($order->status === OrderStatus::Refunded) {
                    // Refunded: 返金として加算
                    $carry[$date]['refunds'] += $total;
                }

                // 更新した $carry を返す → 次のイテレーションの $carry になる
                return $carry;
            },
            [] // 初期値: 空配列（1回目のイテレーションで $carry がこの値になる）
        );

        // 日付でソート
        ksort($dailyData);

        // DailyReport に変換
        return array_values(array_map(
            fn(string $date, array $data) => new DailyReport(
                date: $date,
                orderCount: $data['orderCount'],
                grossSales: $data['grossSales'],
                refunds: $data['refunds'],
                netSales: $data['grossSales'] - $data['refunds'],
                categoryBreakdown: $data['categoryBreakdown'],
            ),
            array_keys($dailyData),
            $dailyData
        ));
    }
}
