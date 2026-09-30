---
description: 備份生產資料庫並下載到本機
allowed-tools: Bash(ssh:*), Bash(scp:*), Bash(mkdir:*)
---

備份生產資料庫，並把備份檔下載到本機保存。

**在執行任何會改動資料庫的操作之前，一律先跑這個。**

```bash
# 1. 在伺服器上備份
ssh -i <key>.pem ubuntu@18.143.26.232 '
  cd /home/ubuntu/churchsys &&
  mkdir -p ~/backup &&
  STAMP=$(date +%Y%m%d_%H%M%S) &&
  docker compose exec -T mysql mysqldump -uroot -p"$DB_ROOT_PASSWORD" \
    --single-transaction --routines --triggers churchsys \
    | gzip > ~/backup/churchsys_${STAMP}.sql.gz &&
  ls -lh ~/backup/churchsys_${STAMP}.sql.gz
'

# 2. 下載到本機（放在 .gitignore 已排除的位置）
mkdir -p ~/churchsys-backups
scp -i <key>.pem ubuntu@18.143.26.232:~/backup/churchsys_*.sql.gz ~/churchsys-backups/
```

驗證備份完整性（檔案大小合理，約 3–5 MB；gzip 可解壓）：

```bash
gz -t ~/churchsys-backups/churchsys_*.sql.gz
```

備份完成後回報：檔名、大小、本機存放路徑。
提醒使用者：**不要把備份檔提交到 git**（`.gitignore` 已排除 `*.sql` 與 `*.sql.gz`）。
