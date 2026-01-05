# Project Rules

## コーディング規約

### src フォルダのルール

- `src/` 配下の PHP ファイルは `declare(strict_types=1);` のみを記述する（クラス定義等は記述しない）

## テストの書き方

- テストメソッド名は日本語で記述する
- `#[Test]` Attribute を使用する（`test` prefix は使わない）

```php
use PHPUnit\Framework\Attributes\Test;

#[Test]
public function 注文を作成できる(): void
{
    // ...
}
```

## 問題追加時のルール

`problems/problemXX/` を追加した場合:

1. `phpunit.xml` に testsuite を追加する

```xml
<testsuite name="problemXX">
  <directory>problems/problemXX/tests</directory>
</testsuite>
```

2. `GUIDE.md` を作成し、実装方針を記載する
   - **実装例（コード）は絶対に記載しない**（コーディングテスト練習用のため）
   - クラス名、メソッド名、シグネチャのみ記載する

```
problems/problemXX/
├── GUIDE.md    # 実装方針ドキュメント
├── src/
└── tests/
```

## コマンド

```bash
# 依存関係インストール
docker compose run --rm php composer install

# オートロード再生成
docker compose run --rm php composer dump-autoload

# テスト実行（全問題）
docker compose run --rm php vendor/bin/phpunit

# テスト実行（個別）
docker compose run --rm php vendor/bin/phpunit --testsuite problemXX
```
