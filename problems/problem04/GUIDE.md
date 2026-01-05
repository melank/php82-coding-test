# Problem 04: Shipping Fee Calculator（送料計算）

## Goal

カート内の商品と配送先、配送手段から「送料」を計算するサービスを実装する。

---

## 送料ルール

### 1. 地域別の基本送料（base fee）

| 地域コード | 基本送料 |
|------------|----------|
| JP-EAST    | 500円    |
| JP-WEST    | 600円    |
| JP-OKINAWA | 1200円   |

### 2. 配送手段による倍率（multiplier）

| 配送手段 | 倍率 |
|----------|------|
| Standard | 1.0  |
| Express  | 1.5  |

### 3. 送料無料条件（free shipping threshold）

- 合計商品価格が threshold 以上なら送料は 0
- **ただし、沖縄（JP-OKINAWA）は送料無料対象外**（常に送料がかかる）

### 4. 危険物（hazmat）手数料

- カート内に `hazmat=true` の商品が1つでも含まれる場合、追加で +300円
- **hazmat 手数料は送料無料判定の後に加算される**
  - 送料無料でも hazmat があれば 300円かかる

---

## 計算順序（重要）

1. cart の商品合計金額 `subtotal` を計算
2. destination の `base fee` を取得
3. shipping method `multiplier` を適用 → `feeBase = (int)ceil(baseFee * multiplier)`
4. 送料無料判定（`subtotal >= threshold` かつ `region != OKINAWA`）なら `feeBase = 0`
5. hazmat 商品が含まれれば `hazmatFee` を加算（送料無料でも加算）
6. 最終送料を int で返す

---

## 入出力

### Input

| パラメータ | 型 | 説明 |
|------------|-----|------|
| `$cart` | `Cart` | 商品の配列を持つカート |
| `$dest` | `Destination` | 配送先（地域コード） |
| `$method` | `ShippingMethod` | 配送手段（Standard / Express） |
| `$policy` | `PricingPolicy` | 送料ルールの設定 |

### Output

- 送料（int、円）

### API シグネチャ

```
ShippingService::calculate(Cart, Destination, ShippingMethod, PricingPolicy): int
```

---

## 実装方針

### 1. Value Object: CartItem

- `price: int` - 商品価格
- `quantity: int` - 数量
- `hazmat: bool` - 危険物フラグ
- コンストラクタでバリデーション

### 2. Value Object: Cart

- `items: list<CartItem>` - 商品リスト
- `subtotal(): int` - 全商品の price * quantity の合計
- `hasHazmat(): bool` - hazmat 商品が含まれるか

### 3. Value Object: Destination

- `regionCode: string` - 地域コード

### 4. Enum: ShippingMethod

- `Standard`
- `Express`

### 5. Value Object: PricingPolicy

- `baseFees: array<string, int>` - 地域別基本送料
- `multipliers: array<string, float>` - 配送手段別倍率
- `freeShippingThreshold: int` - 送料無料閾値
- `hazmatFee: int` - 危険物手数料
- コンストラクタでバリデーション

### 6. Service: ShippingService

- `calculate(Cart, Destination, ShippingMethod, PricingPolicy): int`

---

## Error Handling

以下の条件で `DomainException` をスローする：

| 条件 | 説明 |
|------|------|
| `price <= 0` | 商品価格が0以下 |
| `quantity <= 0` | 商品数量が0以下 |
| 未知の `regionCode` | policy に存在しない地域コード |
| 未知の `ShippingMethod` | policy に存在しない配送手段 |
| `threshold < 0` | 送料無料閾値が負の値 |
| `baseFee < 0` | 基本送料が負の値 |
| `hazmatFee < 0` | 危険物手数料が負の値 |

---

## 実装上の注意

- 金額は int（円）で扱い、小数は使用しない
- multiplier の結果は小数になり得るが、最終送料は **切り上げ（ceil）** で int にする
- **沖縄（JP-OKINAWA）は送料無料対象外**（subtotal が threshold 以上でも送料がかかる）
- **hazmat 手数料は送料無料判定の後に加算**（送料無料でも hazmat があれば 300円）

---

## この課題が「実装力」を見せやすい理由

- 計算順序（送料無料→hazmat加算）の理解と実装で差が出る
- 端数処理（ceil）で細部の正確さが出る
- 入力検証（price/quantity/未知region）で堅牢性が見える
- enum と値オブジェクトのバランス感覚（過剰設計せずに整理できる）
- 集計・条件分岐・例外の基本が揃っており、コーディング力を素直に評価しやすい

---

## テスト実行

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite 04
```
