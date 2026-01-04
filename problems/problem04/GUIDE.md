# Problem 04: Shipping Fee Calculator - 実装方針

## 概要

カート内の商品と配送先、配送手段から「送料」を計算するサービスを実装する。

---

## 送料ルール

### 1. 地域別の基本送料（base fee）

- JP-EAST: 500円
- JP-WEST: 600円
- JP-OKINAWA: 1200円

### 2. 配送手段による倍率（multiplier）

- Standard: 1.0
- Express: 1.5

### 3. 送料無料条件

- 合計商品価格が threshold 以上なら送料は 0
- **沖縄（JP-OKINAWA）は送料無料対象外**

### 4. 危険物（hazmat）手数料

- hazmat=true の商品が含まれる場合、+300円
- **送料無料判定の後に加算**（送料無料でも加算される）

---

## 計算順序（重要）

1. cart の商品合計金額 `subtotal` を計算
2. destination の `base fee` を取得
3. shipping method `multiplier` を適用 → `feeBase`
4. 送料無料判定（`subtotal >= threshold` かつ `region != OKINAWA`）
5. hazmat 商品が含まれれば `hazmatFee` を加算
6. 最終送料を int で返す（端数は切り上げ）

---

## 入出力

### Input

- `$cart`: `Cart`
- `$dest`: `Destination`
- `$method`: `ShippingMethod`
- `$policy`: `PricingPolicy`

### Output

- 送料（int、円）

---

## 実装方針

### 1. Value Object: CartItem

- `public readonly int $price`
- `public readonly int $quantity`
- `public readonly bool $hazmat`
- コンストラクタで `price <= 0` または `quantity <= 0` の場合は `DomainException` を投げる

### 2. Value Object: Cart

- `public readonly array $items` (`CartItem[]`)
- `subtotal(): int` - 全商品の `price * quantity` の合計を返す
- `hasHazmat(): bool` - hazmat 商品が含まれるかを返す

### 3. Value Object: Destination

- `public readonly string $regionCode`

### 4. Enum: ShippingMethod

- `Standard`
- `Express`

### 5. Value Object: PricingPolicy

- `public readonly array $baseFees` (`array<string, int>`)
- `public readonly array $multipliers` (`array<string, float>`)
- `public readonly int $freeShippingThreshold`
- `public readonly int $hazmatFee`
- コンストラクタでバリデーション（負の値チェック）

### 6. Service: ShippingService

- `calculate(Cart $cart, Destination $dest, ShippingMethod $method, PricingPolicy $policy): int`

#### バリデーション

| 条件 | 例外 |
|------|------|
| 未知の regionCode | `DomainException` |
| 未知の ShippingMethod | `DomainException` |

#### 処理フロー

1. cart の subtotal を計算
2. policy から destination の base fee を取得（未知なら例外）
3. policy から method の multiplier を取得（未知なら例外）
4. `feeBase = (int)ceil(baseFee * multiplier)`
5. 送料無料判定: `subtotal >= threshold && regionCode !== 'JP-OKINAWA'` なら `feeBase = 0`
6. hazmat 判定: `cart->hasHazmat()` なら `feeBase += hazmatFee`
7. `feeBase` を返す

---

## 実装上の注意

- 金額は int（円）で扱う
- multiplier 適用後の端数は **ceil で切り上げ**
- 沖縄は送料無料対象外
- hazmat 手数料は送料無料判定の **後** に加算

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
