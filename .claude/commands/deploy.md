---
description: 部署最新程式碼到生產伺服器（Lightsail）
allowed-tools: Bash(ssh:*), Bash(git:*)
---

把目前分支的最新程式碼部署到生產伺服器。

在開始之前，請先確認並回報：

1. `git status` — 是否有未提交的改動？
2. `git log --oneline -3` — 即將部署的是哪些 commit？

若工作目錄不乾淨，先停下來詢問是否要先提交。

部署步驟（伺服器為 `ubuntu@18.143.26.232`，應用路徑 `/home/ubuntu/churchsys`）：

```bash
ssh -i <key>.pem ubuntu@18.143.26.232 '
  cd /home/ubuntu/churchsys &&
  git fetch origin &&
  git status --short &&
  git pull --ff-only &&
  docker compose exec app php artisan config:clear &&
  docker compose exec app php artisan view:clear &&
  docker compose exec app php artisan route:clear &&
  docker compose exec app php artisan migrate --force
'
```

注意事項：

- 只有在 `Dockerfile`、`composer.json` 或 PHP 擴充有變動時才需要
  `docker compose up -d --build`。判斷方式：`git diff --name-only HEAD@{1} HEAD`。
- **不要**執行 `php artisan optimize`。
- 不要動 `.env` 與 `docker/nginx/ssl/`（它們不在版控內）。
- 部署後驗證：`curl -sS -o /dev/null -w '%{http_code}\n' https://churchsys.cmals.org/login`
  應回 200。

完成後回報：部署了哪些 commit、有無 migration、驗證結果。
若任何步驟失敗，**不要**繼續執行後續步驟，先回報錯誤內容。
