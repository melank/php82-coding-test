# Problem 03: Reservation Overlap Checker - 実装方針

## 概要

既存の予約一覧があるとき、新しい予約リクエストが受け入れ可能かを判定するサービスを実装する。

---

## 判定ルール

### 半開区間 [startAt, endAt)

- 開始時刻は **含む**
- 終了時刻は **含まない**
- 例: `10:00-11:00` と `11:00-12:00` は重複 **しない**（OK）
- 例: `10:00-11:00` と `10:59-11:30` は重複 **する**（NG）

### 重複判定

- 2つの区間 A と B が重複する条件: `A.startAt < B.endAt && B.startAt < A.endAt`
- 新規予約が既存予約のいずれかと重複する場合 → 受け入れ不可

### 次に予約可能な時刻

- 受け入れ不可の場合、次に予約可能な最短開始時刻を返す
- 重複した予約帯の終了時刻を起点に、連続する重複予約がある場合はそれらをスキップして次の空きを返す
- 例: `10:00-10:30`, `10:30-11:00` が埋まっている時に `10:15-10:45` を入れたい → 次に空くのは `11:00`

---

## 入出力

### Input

- `$existing`: `TimeRange[]`（時系列順: startAt 昇順）
- `$request`: `ReservationRequest`

### Output

- `ReservationCheckResult`
  - `canReserve`: bool
  - `nextAvailableAt`: ?DateTimeImmutable（canReserve=true のときは null）

---

## 実装方針

### 1. Value Object: TimeRange

- `public readonly DateTimeImmutable $startAt`
- `public readonly DateTimeImmutable $endAt`
- コンストラクタで `startAt >= endAt` の場合は `DomainException` を投げる

### 2. Value Object: ReservationRequest

- `public readonly TimeRange $range`

### 3. Value Object: ReservationCheckResult

- `public readonly bool $canReserve`
- `public readonly ?DateTimeImmutable $nextAvailableAt`

### 4. Service: ReservationService

- `check(array $existing, ReservationRequest $request): ReservationCheckResult`

#### バリデーション

| 条件 | 例外 |
|------|------|
| existing が startAt 昇順でない | `DomainException` |

#### 処理フロー

1. existing の時系列順をチェック（違反なら例外）
2. existing が空なら予約可能（canReserve=true, nextAvailableAt=null）
3. request と各 existing の重複を判定
4. 重複がなければ予約可能
5. 重複があれば:
   - 重複した予約の終了時刻から、連続する予約をスキップ
   - 次の空き時刻を算出して返す

---

## 実装上の注意

- `DateTimeImmutable` を使用すること
- 半開区間の境界条件に注意（終了時刻は含まない）
- existing は startAt 昇順で渡される前提

---

## この課題が「実装力」を見せやすい理由

- 半開区間の境界条件（11:00が重複しないなど）が典型的なバグポイントで精度が出る
- 時系列チェック・例外設計で堅牢性が見える
- 「次に予約可能な時刻」を返すロジックで、単純な重複判定以上の実装力が出る
- DateTimeImmutable を扱うため、実務で頻出の時間比較・走査が練習になる
- foreach と比較だけで解けるため、過剰設計せずにコード品質で差が出る

---

## テスト実行

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite problem03
```
