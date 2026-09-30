---
description: 查看生產環境日誌與容器狀態
allowed-tools: Bash(ssh:*)
---

檢查生產環境的健康狀態。

```bash
ssh -i <key>.pem ubuntu@18.143.26.232 '
  cd /home/ubuntu/churchsys &&
  echo "=== containers ===" && docker compose ps &&
  echo "=== disk ===" && df -h / &&
  echo "=== memory ===" && free -h &&
  echo "=== recent errors ===" && docker compose exec -T app tail -50 storage/logs/laravel.log 2>/dev/null | tail -50
'
```

若指定要看特定服務或行數，$ARGUMENTS

判讀重點：

- 容器 `Up` 但一直重啟 → 檢查是否 `OOMKilled`（主機只有 1.9 GB 且無 swap）。
- `df -h /` 超過 80% → 清理 `storage/logs` 或 Docker 未使用的映像。
- `laravel.log` 出現 `SQLSTATE` → 對照 `docs/TROUBLESHOOTING.md`。
- 出現 `419` 相關紀錄但使用者回報登入被登出 → 檢查 session 目錄權限。

用簡短表格回報，並指出需要處理的項目。
