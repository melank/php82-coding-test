# Problem 01: 注文サービス - 実装方針

## 概要

注文サービス (`OrderService`) を実装し、テストをすべてパスさせる。

---

## 実装方針

### 1. enum OrderStatus

- `Pending` ケースのみでOK（テストが `Pending` を期待）

### 2. Value Object: OrderItem

- `public readonly int $productId`
- `public readonly int $quantity`

### 3. Product Catalog: InMemoryProductCatalog

- `__construct(array $pricesById)` where `array<int, int>`
- `priceOf(int $productId): ?int` を提供

### 4. Entity: Order

- `public readonly int $userId`
- `public readonly OrderStatus $status`
- `private array $items`（`list<OrderItem>`）
- `private array $pricesByProductId`（`InMemoryProductCatalog` は持たせない・単純に）
- `totalQuantity(): int` - items の quantity 合計
- `totalPrice(): int` - 各 item の price × quantity 合計
- `static function pending(int $userId, array $items, array $pricesByProductId): self` で生成を集中

### 5. Usecase: OrderService

- `__construct(private readonly InMemoryProductCatalog $products)`
- `place(int $userId, array $items): Order`

#### バリデーションと例外

| 条件 | 例外 |
|------|------|
| items が空 | `throw new DomainException(...)` |
| quantity <= 0 | `throw new DomainException(...)` |
| カタログに price が無い productId | `throw new DomainException(...)` |

※ 例外メッセージはテストで比較していないので適当で良い

#### 処理フロー

1. 全 items を検証
2. price を収集
3. `Order::pending()` で Order を生成して返す

---

## テスト実行

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite problem01
```
