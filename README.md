# コーディングテスト対策用プロジェクト

PHP 8.2 + PHPUnit 10 の環境をDockerで構築し、TDDでコーディング練習ができるプロジェクトです。

## 必要環境

- Docker
- Docker Compose

## セットアップ

```bash
# 依存関係インストール
docker compose run --rm php composer install
```

## テスト実行

```bash
docker compose run --rm php vendor/bin/phpunit
```

## ディレクトリ構成

```
.
├── docker/
│   └── php/
│       └── Dockerfile
├── docker-compose.yml
├── composer.json
├── phpunit.xml
├── src/                    # 実装コード
│   ├── OrderStatus.php
│   ├── OrderItem.php
│   ├── InMemoryProductCatalog.php
│   ├── Order.php
│   └── OrderService.php
└── tests/                  # テストコード
    └── OrderServiceTest.php
```

## 課題内容

注文サービス (`OrderService`) を実装し、以下のテストをすべてパスさせる。

### 要件

- `OrderService::place(int $userId, array $items): Order`
  - 注文を作成し、`Order` オブジェクトを返す
  - `Order` は `Pending` ステータスで作成される

- `Order::totalQuantity(): int`
  - 注文内の商品数量の合計を返す

- `Order::totalPrice(): int`
  - 注文の合計金額を返す（単価 × 数量の総和）

### 例外条件

| 条件 | 例外メッセージ |
|------|---------------|
| items が空 | `Items cannot be empty` |
| quantity が 0 以下 | `Quantity must be greater than 0` |
| 商品が存在しない | `Product not found: {productId}` |

## 練習方法

1. `src/` 配下のファイルを削除または空にする
2. `docker compose run --rm php vendor/bin/phpunit` でテストが失敗することを確認
3. テストが通るように最小限の実装を行う（TDD）
4. すべてのテストがグリーンになれば完了
