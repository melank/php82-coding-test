# Problem 05: Sales Report Aggregator

## Goal

注文データ（`OrderRecord`）の配列から、日次の売上レポート（`DailyReport`）を生成する。

## Input Data

### OrderRecord

| Field       | Type                    | Description                                      |
|-------------|-------------------------|--------------------------------------------------|
| orderId     | int                     | 注文ID                                           |
| occurredAt  | DateTimeImmutable       | 注文発生日時                                     |
| currency    | string                  | 通貨（"JPY" 固定。異なる通貨は DomainException） |
| items       | list\<OrderItemRecord\> | 注文明細                                         |
| discount    | ?int                    | 割引額（円）。null なら 0。0 未満は DomainException |
| shippingFee | int                     | 送料（円）。0 未満は DomainException             |
| status      | OrderStatus             | 注文ステータス                                   |

### OrderItemRecord

| Field     | Type   | Description                          |
|-----------|--------|--------------------------------------|
| productId | int    | 商品ID                               |
| unitPrice | int    | 単価（円）。0 以下は DomainException |
| quantity  | int    | 数量。0 以下は DomainException       |
| category  | string | カテゴリ（例: "food", "book", "other"） |

### OrderStatus (enum)

- `Paid` - 支払済み（売上として集計）
- `Refunded` - 返金済み（マイナス売上として集計）
- `Cancelled` - キャンセル（集計対象外）

## Calculation

### 注文の小計 (subtotal)

```
subtotal = sum(unitPrice * quantity) for each item
```

### 注文の合計 (total)

```
total = subtotal - discount + shippingFee
```

- `total` が 0 未満になった場合は **0 にクランプ**する

### ステータス別の扱い

| Status    | 扱い                                       |
|-----------|--------------------------------------------|
| Paid      | total を売上として加算                     |
| Refunded  | total をマイナス売上として減算             |
| Cancelled | 集計対象外（件数・金額ともに無視）         |

## Aggregation (DailyReport)

`occurredAt` の日付（YYYY-MM-DD）単位で集計し、**日付昇順**で返す。

### DailyReport の項目

| Field             | Type               | Description                                    |
|-------------------|--------------------|------------------------------------------------|
| date              | string             | 日付（YYYY-MM-DD）                             |
| orderCount        | int                | Paid + Refunded の件数（Cancelled は含めない） |
| grossSales        | int                | Paid のみの total 合計                         |
| refunds           | int                | Refunded の total 合計（正の値で保持）         |
| netSales          | int                | grossSales - refunds                           |
| categoryBreakdown | array\<string,int\>| Paid の items のカテゴリ別売上（unitPrice * quantity） |

### categoryBreakdown の注意

- **Paid の注文のみ**を集計する
- Refunded / Cancelled の items は含めない

## Input / Output

### SalesReportService

```php
class SalesReportService
{
    /**
     * @param list<OrderRecord> $orders
     * @return list<DailyReport> 日付昇順
     * @throws DomainException
     */
    public static function buildDailyReports(array $orders): array;
}
```

## Error Handling

以下の条件で `DomainException` をスローする：

| 条件                     | メッセージ例                    |
|--------------------------|---------------------------------|
| currency が "JPY" 以外   | "Unsupported currency: USD"     |
| discount < 0             | "Discount must not be negative" |
| shippingFee < 0          | "Shipping fee must not be negative" |
| unitPrice <= 0           | "Unit price must be positive"   |
| quantity <= 0            | "Quantity must be positive"     |

## Notes

### 高階関数の活用

この問題は `array_map` / `array_filter` / `array_reduce` を自然に使いたくなるように設計されている。

- `array_filter`: Cancelled 除外、日付範囲抽出
- `array_map`: 注文ごとの total 計算、日付キー付与
- `array_reduce`: items の subtotal 計算、日次集計の accumulator 構築

### 日付の扱い

- `occurredAt` の日付部分（YYYY-MM-DD）で集計する
- タイムゾーンは考慮しない（DateTimeImmutable のまま format する）

### 実装力を見せやすい理由

1. items の subtotal 計算で `array_reduce` が自然に使える
2. Cancelled 除外・日付範囲抽出で `array_filter` が自然に使える
3. order ごとの total / 日付キー化で `array_map` が自然に使える
4. 日次集計は reduce で accumulator を構築する典型問題
5. 境界（clamp、Refunded、Cancelled）と例外（不正値）で正確さが出る
6. ドメインモデルは小さく保ちつつ、集計ロジックでコーディング力が素直に評価される
