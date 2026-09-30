---
description: 執行測試套件（本機或容器內）
allowed-tools: Bash(docker compose:*), Bash(php:*)
---

執行測試套件。

$ARGUMENTS

```bash
docker compose exec app php artisan test
```

若只想跑特定測試：

```bash
docker compose exec app php artisan test --filter=<TestName>
```

判讀結果：

- `tests/Feature/ExampleTest.php` **預期失敗**（`/` 對未登入者回 302），
  這是正常的，不算回歸。
- 其餘測試（`WorshipAdminTakeTest`、`WorshipByWorshipReportTest`、
  `ResponsivePwaTest`）必須全過。
- 若出現 `no such function: YEAR` 之類的錯誤 → 該段程式碼用了 MySQL 專屬函式，
  測試環境是 SQLite，見 `docs/TROUBLESHOOTING.md` §15。

新功能請一併補上測試。測試用 SQLite in-memory，
schema 由 `database/migrations/2026_09_29_000001_create_churchsys_tables.php` 建立，
若需要新表請擴充該 migration。
