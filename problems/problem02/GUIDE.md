# Problem 02: Login Rate Limiter - 実装方針

## 概要

ユーザーのログイン試行履歴から「今ブロックすべきか」を判定するサービスを実装する。

---

## 判定ルール

1. 最新のログイン試行時刻を基準に、直近 `windowMinutes` 分間を対象とする
2. その範囲内で `AttemptResult::Failure` が `failureLimit` 回以上ならブロック
3. `AttemptResult::Success` が発生した場合、その時点で失敗回数はリセットされ、success より前の failure はカウントしない

---

## 入出力

### Input

- `$attempts`: `LoginAttempt` の配列（時系列順: 古い → 新しい）
- `$policy`: `RateLimitPolicy`

### Output

- ブロックすべき場合: `BlockReason::TooManyFailures`
- ブロック不要の場合: `null`

---

## 実装方針

### 1. enum AttemptResult

- `Success`
- `Failure`

### 2. enum BlockReason

- `TooManyFailures`

### 3. Value Object: LoginAttempt

- `public readonly int $userId`
- `public readonly AttemptResult $result`
- `public readonly DateTimeImmutable $occurredAt`

### 4. Value Object: RateLimitPolicy

- `public readonly int $windowMinutes`
- `public readonly int $failureLimit`
- コンストラクタで `windowMinutes <= 0` または `failureLimit <= 0` の場合は `DomainException` を投げる

### 5. Service: RateLimiterService

- `shouldBlock(array $attempts, RateLimitPolicy $policy): ?BlockReason`

#### バリデーション

| 条件 | 例外 |
|------|------|
| attempts が時系列順でない | `DomainException` |

#### 処理フロー

1. attempts が空なら `null` を返す
2. 時系列順かチェック（違反なら例外）
3. 最新の試行時刻を基準にウィンドウ開始時刻を算出
4. ウィンドウ内の試行を走査し、失敗回数をカウント
   - `Success` が出現したら失敗カウントをリセット
5. 失敗回数が `failureLimit` 以上なら `BlockReason::TooManyFailures` を返す
6. それ以外は `null` を返す

---

## 実装上の注意

- `DateTimeImmutable` を使用すること
- 配列は時系列順で処理すること
- ウィンドウ境界（ちょうど N 分前）は含む

---

## この課題が「実装力」を見せやすい理由

- 時間ウィンドウ（直近N分）という実務で頻出のロジックを含む
- success による failure カウントのリセットがあり、単純集計ではない
- 時系列チェック・境界条件（空配列・不正policy）があり、堅牢性が問われる
- enum / readonly / DateTimeImmutable を自然に使えるため、PHP 8.2 らしさを出しやすい
- 過剰設計せず、素直な foreach 実装で差が出る

---

## テスト実行

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite problem02
```
