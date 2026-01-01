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
# 全問題のテストを実行
docker compose run --rm php vendor/bin/phpunit

# 特定の問題のみ実行
docker compose run --rm php vendor/bin/phpunit --testsuite problem01
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
└── problems/
    └── problem01/          # 問題01: 注文サービス
        ├── src/
        │   ├── OrderStatus.php
        │   ├── OrderItem.php
        │   ├── InMemoryProductCatalog.php
        │   ├── Order.php
        │   └── OrderService.php
        └── tests/
            └── OrderServiceTest.php
```

## 新しい問題の追加方法

1. `problems/problemXX/` ディレクトリを作成
2. `src/` と `tests/` を配置
3. `composer dump-autoload` を実行
4. `phpunit.xml` に testsuite を追加（個別実行用）

```bash
# 例: problem02 を追加
mkdir -p problems/problem02/{src,tests}
docker compose run --rm php composer dump-autoload
```

`phpunit.xml` に以下を追加:

```xml
<testsuite name="problem02">
  <directory>problems/problem02/tests</directory>
</testsuite>
```

実行:

```bash
docker compose run --rm php vendor/bin/phpunit --testsuite problem02
```

---

## 練習方法

1. 解きたい問題の `src/` 配下のファイルを削除または空にする
2. テストが失敗することを確認
3. テストが通るように最小限の実装を行う（TDD）
4. すべてのテストがグリーンになれば完了

```bash
# 例: problem01 を練習
rm problems/problem01/src/*.php
docker compose run --rm php vendor/bin/phpunit --testsuite problem01
# テストが失敗する → 実装する → 再度テスト
```
