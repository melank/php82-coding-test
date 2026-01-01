# Project Rules

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
