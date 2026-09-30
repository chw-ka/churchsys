---
description: 查詢生產資料庫（唯讀）
allowed-tools: Bash(ssh:*)
---

對生產資料庫執行唯讀查詢。

要查詢的內容：$ARGUMENTS

連線方式（在伺服器上的容器內執行，不需要另外開埠）：

```bash
ssh -i <key>.pem ubuntu@18.143.26.232 '
  cd /home/ubuntu/churchsys &&
  docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" \
    --default-character-set=utf8mb4 churchsys -e "<SQL>"
'
```

規則：

- **只執行 `SELECT`。** 任何 `UPDATE` / `DELETE` / `DROP` / `ALTER` / `TRUNCATE`
  都必須先停下來，向使用者說明影響範圍並取得明確同意。
- 一定要加 `--default-character-set=utf8mb4`，否則中文會顯示成 `???`。
- 大表查詢請加 `LIMIT`（`tbl_worship_attendance` 有約 22 萬筆）。
- 若查詢涉及日期，記得資料庫存的是**香港本地時間**。

先說明你打算跑的 SQL，然後執行，最後用表格整理結果。
