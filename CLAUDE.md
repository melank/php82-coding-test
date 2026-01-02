# Project Rules

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

`problems/problemXX/` を追加した場合、必ず `phpunit.xml` に testsuite を追加すること。

```xml
<testsuite name="problemXX">
  <directory>problems/problemXX/tests</directory>
</testsuite>
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
