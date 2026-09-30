# 部署與維運

> 相關文件：[`../CLAUDE.md`](../CLAUDE.md)、[`ARCHITECTURE.md`](ARCHITECTURE.md)、[`DATABASE.md`](DATABASE.md)

---

## 1. 環境一覽

| 項目 | 值 |
|---|---|
| 平台 | AWS Lightsail |
| 靜態 IP | `18.143.26.232` |
| 區域 | ap-southeast-1a（新加坡） |
| 規格 | 2 GB RAM / 2 vCPU / 60 GB SSD |
| 作業系統 | Ubuntu 24.04.4 LTS (noble) |
| SSH 帳號 | `ubuntu`（有 sudo） |
| 應用路徑 | `/home/ubuntu/churchsys` |
| 對外服務 | 80 / 443 |
| 網域 | <https://churchsys.cmals.org> |
| 磁碟使用 | 約 5.8 GB / 58 GB |
| 記憶體 | 1.9 GB，**無 swap** |
| Docker | 29.1.3 |
| Docker Compose | 2.40.3 |

### SSH 連線

```bash
ssh -i <lightsail-key>.pem ubuntu@18.143.26.232
```

金鑰可從 Lightsail 主控台下載（`LightsailDefaultKey-ap-southeast-1.pem`）。
若需為他人開通存取，請將其 **public key** 加入 `~/.ssh/authorized_keys`，
不要共用私鑰：

```bash
echo "ssh-ed25519 AAAA... user@laptop" >> ~/.ssh/authorized_keys
```

---

## 2. 容器

由 `docker-compose.yml` 定義三個服務：

| 容器 | 映像 | 對外埠 | 說明 |
|---|---|---|---|
| `churchsys-web` | `nginx:alpine` | 80, 443 | 反向代理、TLS 終結、靜態檔案 |
| `churchsys-app` | 自建（`docker/php/Dockerfile`） | 無 | php:8.3-fpm + pdo_mysql / mbstring / gd / zip |
| `churchsys-mysql` | `mysql:8.0` | 127.0.0.1:3306 | 資料持久化於 `mysql_data` volume |

`app` 與 `web` 都把專案目錄 bind mount 至 `/var/www/html`，
所以修改程式碼後**不需要重建映像**。

```bash
docker compose ps
docker compose logs -f app          # 追蹤 PHP 日誌
docker compose logs --tail=100 web
docker compose restart app
docker compose up -d --build        # 僅在 Dockerfile / PHP 擴充有變動時
```

> **記憶體警示**：主機只有 1.9 GB 且**沒有 swap**，同時跑 MySQL 8 + PHP-FPM + nginx
> 屬偏緊。目前 `pm.max_children = 5`、`innodb_buffer_pool_size = 256M` 已調降。
> 若出現容器被 OOM Kill（`docker inspect` 見到 `OOMKilled`），請先加 2 GB swap：
> ```bash
> sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile
> sudo mkswap /swapfile && sudo swapon /swapfile
> echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
> ```

---

## 3. 部署新版本

伺服器上的 `/home/ubuntu/churchsys` 已接上 git remote（`origin`），部署即 `git pull`。

```bash
ssh -i <key>.pem ubuntu@18.143.26.232
cd /home/ubuntu/churchsys

git fetch origin
git status                      # 確認沒有意外的本機修改
git pull --ff-only

# 只在依賴或 Dockerfile 有變動時才需要
docker compose up -d --build

# 清快取（幾乎每次改動 config / view 後都需要）
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan route:clear
docker compose exec app php artisan migrate --force
```

### 注意事項

- **不要執行 `php artisan optimize` 或 `config:cache`。** 本專案的 `.env` 內含
  容器網絡主機名（`mysql`），快取後除錯會變得很麻煩，而效能收益有限。
- `docker/nginx/ssl/` 與 `.env` **不在版控內**，`git pull` 不會覆蓋它們。
  若 `git status` 顯示這兩者被追蹤，代表 `.gitignore` 被改壞了。
- 檔案擁有者：專案目錄為 `www-data`，部分目錄為 `ubuntu`。
  若出現權限錯誤，執行 `sudo docker compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache`。

### 回滾

```bash
git log --oneline -10
git checkout <上一個正常的 commit>
docker compose exec app php artisan view:clear
```

---

## 4. TLS / SSL

憑證由 **certbot 2.9.0** 以 **webroot** 方式簽發與續期。

| 項目 | 值 |
|---|---|
| 憑證路徑 | `/etc/letsencrypt/live/churchsys.cmals.org/` |
| 簽發方式 | webroot，`webroot_path = /home/ubuntu/churchsys/public` |
| 金鑰類型 | ECDSA |
| 續期排程 | `certbot.timer`（每日兩次） |
| 到期日 | 2026-12-28（自動續期） |

nginx 讀取的憑證位於 `docker/nginx/ssl/`（掛載至容器 `/etc/nginx/ssl`），
由 deploy hook 從 `/etc/letsencrypt/` 複製過來：

```
/etc/letsencrypt/renewal-hooks/deploy/churchsys-copy.sh
```

內容（亦保存於 `deploy/certbot/churchsys-copy.sh`）：

```bash
#!/bin/bash
cp -L /etc/letsencrypt/live/churchsys.cmals.org/fullchain.pem /home/ubuntu/churchsys/docker/nginx/ssl/fullchain.pem
cp -L /etc/letsencrypt/live/churchsys.cmals.org/privkey.pem  /home/ubuntu/churchsys/docker/nginx/ssl/privkey.pem
chown ubuntu:ubuntu /home/ubuntu/churchsys/docker/nginx/ssl/*.pem
chmod 644 /home/ubuntu/churchsys/docker/nginx/ssl/fullchain.pem
chmod 600 /home/ubuntu/churchsys/docker/nginx/ssl/privkey.pem
docker exec churchsys-web nginx -s reload
```

### 手動續期測試

```bash
sudo certbot renew --dry-run
sudo certbot certificates
```

### 全新伺服器的簽發步驟

```bash
sudo apt install -y certbot
cd /home/ubuntu/churchsys
# HTTP-01 驗證需要先有可回應 80 埠的 nginx（此時可先用只監聽 80 的設定）
sudo certbot certonly --webroot -w /home/ubuntu/churchsys/public -d churchsys.cmals.org
sudo cp deploy/certbot/churchsys-copy.sh /etc/letsencrypt/renewal-hooks/deploy/churchsys-copy.sh
sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/churchsys-copy.sh
sudo /etc/letsencrypt/renewal-hooks/deploy/churchsys-copy.sh
```

---

## 5. DNS

### 目前狀態（2026-09-30 實測）

| 名稱 | 指向 | 狀態 |
|---|---|---|
| `churchsys.cmals.org` | `18.143.26.232`（Lightsail） | ✅ 正確，HTTPS 200 |
| `test.cmals.org` | `18.143.26.232`（同一台） | ⚠️ 相同主機但憑證只涵蓋 `churchsys` 子網域 |
| `cmals.org`（apex） | `54.169.156.17`（**舊 EC2**） | ❌ 應轉向 `cmals.org.hk`；目前憑證已過期（`tommakdesign.com`，2025-03-28 到期） |
| `www.cmals.org` | `3.33.152.147` / `15.197.142.173` | ❌ GoDaddy 停放頁，應轉向 `cmals.org.hk` |
| `cmals.org.hk` | `192.0.78.24/25`（Wix） | ✅ 正常 |

### 待辦

1. **`cmals.org` 與 `www.cmals.org` 應轉向 <https://cmals.org.hk>。**
   在 GoDaddy 將 `@` 與 `www` 設為 forwarding（301）至 `https://cmals.org.hk`，
   並移除指向舊 EC2 的 A 記錄。
2. **`test.cmals.org`** 目前與生產指向同一台機器，但憑證不涵蓋它，
   瀏覽器會顯示憑證名稱不符。建議二選一：
   - 刪除該 DNS 記錄（測試環境已由正式環境取代）；或
   - 為它單獨簽發憑證並在 nginx 加一個 `server_name test.cmals.org` 的 vhost。

### ⚠️ 停用舊 EC2 前必須先完成

舊 EC2 `54.169.156.17` 仍在回應 `cmals.org`。**在 `cmals.org` 的 DNS 轉向
`cmals.org.hk` 之前，不可關閉 EC2**，否則 `cmals.org` 會完全無法連線。
`churchsys.cmals.org` 已獨立指向 Lightsail，不受影響。

---

## 6. S3 物件儲存（會友相片）

舊系統把會員相片放在 S3，新系統沿用同一 bucket，以 s3fs 掛載。

| 項目 | 值 |
|---|---|
| Bucket | `www.cma-livingstones.org` |
| 區域 | ap-southeast-1 |
| 掛載點 | `/mnt/s3-drive` |
| 容器內路徑 | `public/storage/file`（唯讀掛載） |
| 憑證檔 | `/etc/passwd-s3fs`（root, 600） |

開機自動掛載（`/etc/fstab`）：

```
s3fs#www.cma-livingstones.org /mnt/s3-drive fuse _netdev,allow_other,use_cache=/tmp,mp_umask=002,multireq_max=5,use_path_request_style,url=https://s3-ap-southeast-1.amazonaws.com 0 0
```

```bash
mount | grep s3fs        # 確認已掛載
sudo mount /mnt/s3-drive # 手動掛載
```

由於容器內是**唯讀**掛載，管理頁面上的相片上傳目前只會寫入容器內的
`public/storage/file/`，**不會**持久化到 S3。若教會需要上傳新相片，
需先改為讀寫掛載並確認 S3 寫入權限。

---

## 7. 備份

### 資料庫

```bash
cd /home/ubuntu/churchsys
docker compose exec -T mysql mysqldump -uroot -p"$DB_ROOT_PASSWORD" \
  --single-transaction --routines --triggers churchsys \
  | gzip > ~/backup/churchsys_$(date +%Y%m%d_%H%M%S).sql.gz
```

### 建議的 cron（目前尚未設定）

```
# 每日 03:00 備份資料庫，保留 14 天
0 3 * * * cd /home/ubuntu/churchsys && docker compose exec -T mysql mysqldump -uroot -p"$DB_ROOT_PASSWORD" --single-transaction --routines --triggers churchsys | gzip > /home/ubuntu/backup/churchsys_$(date +\%Y\%m\%d).sql.gz && find /home/ubuntu/backup -name 'churchsys_*.sql.gz' -mtime +14 -delete
```

> 目前**沒有**自動備份。由於 Lightsail 的 snapshot 與本機備份都在同一帳號下，
> 建議至少把備份檔案同步到 S3 或其他位置。

### 舊 EC2 備份

2026-09-29 已完成完整備份，內容包括資料庫 dump、Apache 設定、`/var/www` 壓縮檔
（約 1.12 GB），並已以 md5 核對。

---

## 8. 新伺服器重建流程

若需從零建立一台新機器：

```bash
# 1. 安裝 Docker
sudo apt update && sudo apt install -y docker.io docker-compose-v2 git
sudo usermod -aG docker ubuntu && newgrp docker

# 2. 取得程式碼
git clone <repo-url> /home/ubuntu/churchsys
cd /home/ubuntu/churchsys

# 3. 放入 .env（由系統擁有者提供），確認 APP_KEY / DB 密碼
#    APP_TIMEZONE 必須是 Asia/Hong_Kong

# 4. 啟動
docker compose up -d --build
docker compose exec app php artisan key:generate   # 只在 APP_KEY 為空時

# 5. 還原資料庫
gunzip -c churchsys_backup.sql.gz | \
  docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys

# 6. 簽發憑證（見 §4）
# 7. 掛載 S3（見 §6）
# 8. 設定防火牆：只開放 22 / 80 / 443
```

Lightsail 的防火牆（Networking → IPv4 Firewall）請只保留 SSH(22)、HTTP(80)、HTTPS(443)。
MySQL 的 3306 已綁定 `127.0.0.1`，不對外開放。

---

## 9. 日常維運檢查清單

```bash
# 容器狀態
docker compose ps

# 磁碟與記憶體
df -h / ; free -h

# 應用日誌（最後 50 行錯誤）
docker compose exec app tail -50 storage/logs/laravel.log

# 憑證到期
sudo certbot certificates

# s3fs 掛載
mount | grep s3fs

# 網站回應
curl -sS -o /dev/null -w '%{http_code}\n' https://churchsys.cmals.org/login
```
