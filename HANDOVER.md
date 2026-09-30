# 交接清單

本檔案說明接手 ChurchSys 需要取得哪些憑證與存取權，以及如何驗證環境已就緒。

> **本檔案不含任何真實憑證。** 所有密碼與金鑰由系統擁有者另行以安全渠道提供。

儲存庫：<https://github.com/chw-ka/churchsys>（**public**，clone 無需 GitHub 帳號）

> **隱私注意**：`docs/screenshots/` 內只保留不含個人資料的截圖；
> 含會友個資的截圖已從整個 git 歷史移除，**切勿**把任何生產畫面截圖
> （會友姓名、電話、住址）提交進這個公開儲存庫。

---

## 1. 需要取得的項目

| # | 項目 | 用途 | 由誰提供 | 存放位置（取得後） |
|---|---|---|---|---|
| 1 | **生產 `.env`** | 應用的完整環境設定（`APP_KEY`、資料庫密碼） | 系統擁有者 | 專案根目錄 `.env`，**不要提交** |
| 2 | **Lightsail SSH 私鑰** | 登入生產伺服器部署與排查 | 系統擁有者，或 Lightsail 主控台 | `~/.ssh/`（權限 600） |
| 3 | **Git 寫入權（僅改程式碼時需要）** | 把修改推回 GitHub | 註冊免費 GitHub 帳號後由系統擁有者邀請為協作者；或改用下方「無 GitHub 帳號的部署方式」 | — |
| 4 | **AWS 存取權**（可選） | 管理 Lightsail 執行個體、S3 bucket、檢視快照 | 系統擁有者 | — |

> 伺服器本身**不需要**你的 GitHub 憑證：它已用一組唯讀的 SSH deploy key
> （`~/.ssh/churchsys_deploy`，GitHub 上標題為 "Lightsail production (read-only)"）
> 接上 `origin`，因此 `git pull` 可直接運作、而且無法從伺服器推送。

### 1.0 沒有 GitHub 帳號時的工作流

儲存庫是 public，所以**讀取完全不需要帳號**：

```bash
git clone https://github.com/chw-ka/churchsys.git churchsys && cd churchsys
```

只有「把修改推回 GitHub」需要帳號。在取得帳號之前，改程式碼後的部署方式：

```bash
# 1. 在本機改好、跑完測試後，把改動的檔案同步上伺服器
rsync -av --exclude '.git' --exclude '.env' --exclude 'vendor/' \
      ./ ubuntu@18.143.26.232:/home/ubuntu/churchsys/

# 2. 在伺服器以本地 commit 記錄這次變更（不需 GitHub）
ssh ubuntu@18.143.26.232 'cd /home/ubuntu/churchsys && git add -A && git commit -m "描述"'

# 3. 待你日後取得 GitHub 帳號，再把兩邊歷史合流（屆時找系統擁有者協助）
```

> 日常維運（看 log、重啟容器、備份、查資料庫）**完全不需要改程式碼**，
> 也就完全不需要 GitHub 帳號——見 `docs/DEPLOYMENT.md`。

### 1.1 關於 SSH 存取

建議**不要**共用私鑰。正確做法是把你的 **public key** 交給系統擁有者，
由他在伺服器上加入授權：

```bash
# 在你自己的機器上產生（若尚未有金鑰）
ssh-keygen -t ed25519 -C "your-name@churchsys"

# 把 ~/.ssh/id_ed25519.pub 的內容交給系統擁有者，由他在伺服器執行：
echo "ssh-ed25519 AAAA... your-name@churchsys" >> ~/.ssh/authorized_keys
```

### 1.2 關於資料庫

生產資料庫的 `root` 與應用帳號密碼都在 `.env` 內。**日常不需要直接連生產
資料庫**——透過伺服器上的容器即可：

```bash
ssh -i <key>.pem ubuntu@18.143.26.232
cd /home/ubuntu/churchsys
docker compose exec mysql mysql -uroot -p"$DB_ROOT_PASSWORD" \
  --default-character-set=utf8mb4 churchsys
```

建議在本機建立**獨立**的開發資料庫（用 `deploy/sql/00_schema.sql` 建結構，
再匯入一份不含真實個資的測試資料），不要直接連生產資料庫開發。

---

## 2. 環境就緒驗證

依序執行，全部通過即代表交接完成。

```bash
# 1. 取得原始碼
git clone <repo-url> churchsys && cd churchsys

# 2. 環境設定
cp .env.example .env
# 把系統擁有者提供的生產 .env 內容填入（或自行設定本機值）
# 確認 APP_TIMEZONE=Asia/Hong_Kong

# 3. 啟動
docker compose up -d --build
docker compose ps                      # 三個容器都應為 Up

# 4. 資料庫結構
docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys < deploy/sql/00_schema.sql

# 5. 應用啟動
docker compose exec app php artisan key:generate   # 只在 APP_KEY 為空時
docker compose exec app php artisan config:clear
curl -sS -o /dev/null -w '%{http_code}\n' http://localhost/login   # 應為 200

# 6. 測試（應為 39 passed）
docker compose exec app php artisan test

# 7. 伺服器存取
ssh -i <key>.pem ubuntu@18.143.26.232 'cd /home/ubuntu/churchsys && docker compose ps'
```

---

## 3. 讀取順序建議

| 順序 | 檔案 | 為什麼 |
|---|---|---|
| 1 | [`CLAUDE.md`](CLAUDE.md) | 領域規則、關鍵不變量、禁區。**最重要。** |
| 2 | [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | 系統全貌、路由表、三個核心流程 |
| 3 | [`docs/DATABASE.md`](docs/DATABASE.md) | 資料表結構與欄位語意 |
| 4 | [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | 部署、SSL、DNS、備份 |
| 5 | [`docs/TROUBLESHOOTING.md`](docs/TROUBLESHOOTING.md) | 已踩過的坑，動手前先看 |

若使用 Claude Code，`CLAUDE.md` 會自動載入，其餘按需要查閱。

若要把整個系統交給 Claude（Claude Cowork / Claude Code）代管，
可直接使用 [`docs/PROMPT_FOR_CLAUDE.md`](docs/PROMPT_FOR_CLAUDE.md) 內的現成 prompt。

---

## 4. 交接當下的系統狀態

> 記錄時間：2026-09-30，以下項目均已實測驗證。

| 項目 | 狀態 |
|---|---|
| 生產環境 | Lightsail `18.143.26.232`，`https://churchsys.cmals.org`（HTTP 200） |
| 資料庫 | 4,723 位會友、219,860 筆出席紀錄、58 個帳號、31 張表 |
| TLS 憑證 | 有效至 2026-12-28，`certbot.timer` 自動續期 |
| 版本控制 | 生產目錄已接上 git remote（SSH deploy key，唯讀），部署改為 `git pull` |
| 測試 | 39 passed / 174 assertions，全部在 SQLite in-memory 執行 |
| 舊 EC2 | `54.169.156.17` 仍在運作，已完成完整備份，**待停用** |

### 交接時一併修正的問題

| 問題 | 影響 | 處理 |
|---|---|---|
| 應用時區為 UTC，但資料庫時間戳是香港時間 | 新簽到會早 8 小時，清晨簽到甚至記到前一日 | `config/app.php` 改為 `Asia/Hong_Kong`，已實測寫入正確 |
| `php artisan migrate` 會在生產建立多餘的 `users` 等空表後中斷 | 生產 schema 被靜默改動 | 三個 Laravel 骨架 migration 改為單一冪等版本，已實測為空操作 |
| 測試套件連上生產資料庫 | `RefreshDatabase` 會執行 `migrate:fresh`，可能刪光教會資料 | 加入 `tests/bootstrap.php` 強制設定 + `TestCase` 連線斷言 |
| `ExampleTest` 斷言 `/` 回 200，但該路由需要登入 | 測試永遠有一個紅燈 | 改為 `AuthenticationTest` 與 `DomainTest` |

上述四項的成因與排查方式都寫進了 [`docs/TROUBLESHOOTING.md`](docs/TROUBLESHOOTING.md)（§2、§16、§17）。

### 尚未完成的事項

1. **`cmals.org` 與 `www.cmals.org` 的 DNS 轉向未完成。**
   兩者目前都不指向 `cmals.org.hk`（`cmals.org` 指向舊 EC2 且憑證已過期）。
   **在完成轉向之前不可關閉舊 EC2。**
   詳見 [`DEPLOYMENT.md`](DEPLOYMENT.md) §5。

2. **`test.cmals.org`** 與生產指向同一台機器，但憑證不涵蓋它，
   瀏覽器會顯示憑證名稱不符。建議刪除或單獨簽發憑證。

3. **自動備份尚未設定。** 建議加入每日 cron 並同步至異地。

4. **舊 EC2 尚未關閉。** 待上述第 1 項完成、且備份已驗證可還原後再關閉。

5. **`CLAUDE.md` §13 列出的已知問題尚未修復**（刪除出席紀錄功能損壞、
   簽到時間顯示 `00:00`、缺少 `Group` 模型、無權限分層）。

---

## 5. 需要向教會確認的業務問題

技術以外的問題，動手修改前請先問清楚：

1. **`tbl_member.account_type = 2` 的語意。**
   資料庫欄位註解寫「已離世」，但程式顯示為「同工」。36 筆資料。
   兩者意義完全不同，請確認哪一個才對。

2. **報告數字的定義。**
   八個報告頁沿用舊系統的 SQL。若要「優化」或改寫，請先請教會確認
   既有報告的計算方式，以免數字對不上。

3. **相片上傳的預期行為。**
   S3 目前以唯讀方式掛載，所以管理頁面上傳的相片不會持久化。

4. **是否需要角色權限分層。**
   目前任何登入者都能刪除會友、修改崇拜設定。教會是否需要區分權限？
