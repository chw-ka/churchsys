# 資料庫

> 相關文件：[`../CLAUDE.md`](../CLAUDE.md)、[`ARCHITECTURE.md`](ARCHITECTURE.md)、[`DEPLOYMENT.md`](DEPLOYMENT.md)

---

## 1. 連線資訊

| 項目 | 值 |
|---|---|
| 引擎 | MySQL 8.0（容器 `churchsys-mysql`） |
| 位址（容器內） | `mysql:3306` |
| 位址（主機） | `127.0.0.1:3306`（僅綁本機） |
| 資料庫 | `churchsys` |
| 帳號 | `churchsys`（應用）／`root`（管理） |
| 密碼 | 見 `.env` 的 `DB_PASSWORD` / `DB_ROOT_PASSWORD` |
| 字元集 | `utf8mb4` / `utf8mb4_unicode_ci` |
| 時區 | 無時區轉換；`DATETIME` 欄位存香港本地時間（見 [`../CLAUDE.md`](../CLAUDE.md) §6.1） |
| 資料量 | 約 219,860 筆出席紀錄、4,723 位會友、58 個帳號 |

以命令列查中文時請加 `--default-character-set=utf8mb4`，否則會看到 `???`：

```bash
docker compose exec mysql mysql -uroot -p"$DB_ROOT_PASSWORD" \
  --default-character-set=utf8mb4 churchsys -e "SELECT id, code, name FROM tbl_member LIMIT 5;"
```

### 歷史沿革

| 階段 | 環境 | 說明 |
|---|---|---|
| 2007–2026/09 | AWS EC2 `54.169.156.17` | Ubuntu 16.04 + Apache + PHP 7.0 + MySQL 5.7，Yii 1.1 應用 `churchsys2` |
| 2026/09 起 | AWS Lightsail `18.143.26.232` | Ubuntu 24.04 + Docker + PHP 8.3 + MySQL 8，Laravel 13 |

資料庫於 2026-09-29 由 EC2 遷移過來，逐表筆數已核對一致。舊 EC2 已完整備份
（資料庫 dump、Apache 設定、`/var/www` 壓縮檔），待停用。

---

## 2. 資料表總覽

### 2.1 應用實際使用的表（5 張核心 + 2 張關聯）

| 表 | 筆數 | 用途 |
|---|---|---|
| `tbl_member` | 4,723 | 會友主檔 |
| `tbl_worship` | 13 | 崇拜場次定義 |
| `tbl_worship_attendance` | 219,860 | **崇拜出席紀錄（系統核心資料）** |
| `tbl_user` | 58 | 後台登入帳號 |
| `tbl_group_period` | 11 | 小組期間（報告頁分組用） |
| `tbl_group` | 48 | 小組 |
| `tbl_group_member` | 2,780 | 小組成員 |

### 2.2 舊系統遺留表（現行程式未使用）

| 表 | 筆數 | 原用途 |
|---|---|---|
| `tbl_group_attendance` | 5,147 | 小組出席 |
| `tbl_hymn` / `tbl_hymn_tags` | 1,087 / 2 | 詩歌庫 |
| `tbl_pledge` / `tbl_pledge_member` | 4 / 163 | 奉獻／承諾 |
| `tbl_issues` / `tbl_issues_reply` | 39 / 28 | 內部問題回報 |
| `tbl_summer_activity` / `_participant` | 6 / 14 | 暑期活動 |
| `tbl_course` / `_attendance` / `_member` | 4 / 81 / 17 | 課程與出席 |
| `tbl_member_relationship` | 0 | 會友關係 |
| `tbl_worship_greeting` / `tbl_worship_remarks` | 0 / 0 | 崇拜問候／備註 |
| `AuthItem` / `AuthItemChild` / `AuthAssignment` / `Rights` | 63 / 55 / 55 / 0 | 舊 Yii RBAC 權限 |
| `cache` / `cache_locks` / `jobs` / `job_batches` / `failed_jobs` | 0 | Laravel 框架表 |
| `migrations` | — | Laravel migration 紀錄（追蹤兩個冪等 migration） |

> **不要刪除遺留表。** 教會日後可能要求把小組、詩歌、課程等模組補回 Laravel 版本，
> 這些歷史資料是唯一來源。移除前請先取得教會書面同意。

---

## 3. 核心表結構

### 3.1 `tbl_member` — 會友

```sql
id            INT AUTO_INCREMENT PK
state         TINYINT        -- 1 有效、0 已刪除、2 已離世（實際資料只有 0 與 1）
code          VARCHAR(10)    -- 會友編號，簽到識別碼；刪除時清空為 ''
name          VARCHAR(255)   -- 中文姓名，允許重名
remarks       MEDIUMTEXT
english_name  VARCHAR(255)
photo         VARCHAR(255)
gender        TINYINT        -- 1 女、2 男、3 未知
birthday      DATE
email         VARCHAR(255)
believe       VARCHAR(255)   -- 信主
believe_date  VARCHAR(255)   -- 注意：是字串不是日期
baptized      VARCHAR(255)   -- 受浸
baptized_date VARCHAR(255)   -- 注意：是字串不是日期
account_type  INT            -- 0 新朋友、1 會友、2 同工（見下方警告）
new_card      INT
arrived_date  DATE
create_date   TIMESTAMP      -- DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
modify_date   TIMESTAMP      -- DEFAULT '1970-01-01 00:00:01'
creator_id    INT
modifier_id   INT
address_district / address_estate / address_house / address_flat  VARCHAR(255)
contact_home / contact_mobile / contact_office / contact_others    VARCHAR(255)
```

實際分布：`state=1` 3,835 筆、`state=0` 888 筆；
`account_type` 為 0 者 2,079、1 者 1,720、2 者 36、-1 者 888（與 `state=0` 完全對應）。

> ⚠️ **`account_type = 2` 的語意有歧義。** 資料庫欄位註解寫 `2: Passed away`（已離世），
> 但 `Member::ACCOUNT_TYPE_CO_MEMBER = 2` 對應的顯示文字是「同工」。36 筆資料。
> **修改這部分前請先與教會確認**，不要自行認定。

> ⚠️ **`create_date` 帶有 `ON UPDATE CURRENT_TIMESTAMP`。** 這是舊系統的特性：
> 任何 UPDATE 都會連帶改寫 `create_date`。因此 `create_date` **不是**可靠的「建立時間」。
> `modify_date` 才是最後修改時間。

刪除是**軟刪除**：`state → 0`、`code → ''`、`account_type → -1`。紀錄保留。

### 3.2 `tbl_worship` — 崇拜場次

```sql
id         INT AUTO_INCREMENT PK
state      INT          -- 1 啟用、0 停用
name       VARCHAR(255) -- 例：主日第一堂
start_time TIME
end_time   TIME
weekly     INT          -- 0 週日、1 週一 … 6 週六（是「星期幾」，不是「每週」）
remarks    MEDIUMTEXT
```

現行啟用場次：

| id | 名稱 | weekly | 時間 |
|---|---|---|---|
| 1 | 主日第一堂 | 0（週日） | 08:00–09:25 |
| 18 | 宣道園崇拜 | 0（週日） | 09:30–10:25 |
| 3 | 主日第二堂 | 0（週日） | 10:30–12:00 |
| 12 | 週六崇拜 | 6（週六） | 16:00–18:00 |

其餘 9 筆為 `state = 0` 的停用或測試資料（多筆名稱叫 `testing`）。

### 3.3 `tbl_worship_attendance` — 崇拜出席 ★

```sql
worship_id      INT      ┐
member_id       INT      ├ PRIMARY KEY (worship_id, member_id, attendance_date)
attendance_date DATETIME ┘

FOREIGN KEY (worship_id) REFERENCES tbl_worship (id)
FOREIGN KEY (member_id)  REFERENCES tbl_member (id)
INDEX idx_attendance_date (attendance_date)
```

**沒有 `id` 欄位。** 這是既有設計（同一人同一崇拜同一時刻只能有一筆）。

- `attendance_date` 存**香港本地時間**（見 [`../CLAUDE.md`](../CLAUDE.md) §6.1）。
- 外鍵沒有 CASCADE：刪除會友不會連帶刪除出席紀錄（出席紀錄獨立保存）。
- 時間戳記錄「簽到當下」，補加出席則用「指定日期 + 執行當下的時分秒」。
- 索引 `idx_attendance_date` 是為了各種日期區間查詢；**不要移除**。

### 3.4 `tbl_user` — 帳號

```sql
id                  INT AUTO_INCREMENT PK
username            VARCHAR(128) UNIQUE
password            VARCHAR(255)  -- 舊資料為 MD5(32)，登入成功後自動升級為 bcrypt
password_reset_token VARCHAR(255)
remember_token      VARCHAR(100)
last_login_at       TIMESTAMP NULL  -- 由 Laravel 新增的欄位
email               VARCHAR(128)
member_code         VARCHAR(4)
create_time         DATETIME
update_time         DATETIME
```

`config/auth.php` 已指向 `App\Models\User`。**不要**改用 Laravel 預設的 `users` 表。

---

## 4. 常用查詢

```sql
-- 會友基本資料
SELECT id, code, name, gender, account_type FROM tbl_member WHERE state = 1 ORDER BY code LIMIT 20;

-- 某日的出席名單（依崇拜分組）
SELECT w.name AS worship, m.code, m.name AS member, a.attendance_date
FROM tbl_worship_attendance a
JOIN tbl_member m  ON m.id = a.member_id
JOIN tbl_worship w ON w.id = a.worship_id
WHERE DATE(a.attendance_date) = '2026-09-27'
ORDER BY w.id, a.attendance_date;

-- 各崇拜近 8 週出席數
SELECT w.name, DATE(a.attendance_date) AS d, COUNT(*) AS n
FROM tbl_worship_attendance a
JOIN tbl_worship w ON w.id = a.worship_id
WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 56 DAY)
GROUP BY w.id, d
ORDER BY d DESC, w.id;

-- 出席紀錄最多的會友
SELECT m.code, m.name, COUNT(*) AS n
FROM tbl_worship_attendance a
JOIN tbl_member m ON m.id = a.member_id
GROUP BY m.id ORDER BY n DESC LIMIT 20;

-- 資料完整度檢查：每日出席筆數與時間範圍（用於猜測時區是否正確）
SELECT DATE(attendance_date) AS d,
       TIME(MIN(attendance_date)) AS first_t,
       TIME(MAX(attendance_date)) AS last_t,
       COUNT(*) AS n
FROM tbl_worship_attendance
WHERE attendance_date >= '2026-09-01'
GROUP BY d ORDER BY d;
```

最後一條是判斷時區問題的標準手法：正常結果的時間應落在 08:00–12:00（主日）
或 16:00–18:00（週六）。若看到凌晨 00:00–04:00 的紀錄，代表應用時區設定被改成 UTC。
詳見 [`TROUBLESHOOTING.md`](TROUBLESHOOTING.md)。

---

## 5. 變更資料庫結構

### 5.1 本機／測試

`database/migrations/` 內只有兩個應用專屬 migration：

| Migration | 內容 |
|---|---|
| `0001_01_01_000000_create_framework_tables.php` | `cache`、`cache_locks`、`jobs`、`job_batches`、`failed_jobs` |
| `2026_09_29_000001_create_churchsys_tables.php` | `tbl_user`、`tbl_member`、`tbl_worship`、`tbl_worship_attendance` |

兩者的 `up()` 都是**冪等**的——每一張表都先以 `Schema::hasTable()` 檢查才建立，
所以 `php artisan migrate` 在任何環境執行都安全（在生產環境是空操作）。

`2026_09_29_000001` 建立的只是**最小**結構，足以讓全新安裝與 SQLite 測試運作。
完整結構（30 張表，含遺留的小組、課程、詩歌表）見 `deploy/sql/00_schema.sql`。
若要在測試中用到其他表，請擴充這個 migration 而非新增。

> **本專案沒有 Laravel 預設的 `users`、`password_reset_tokens`、`sessions` 表。**
> 認證讀 `tbl_user`（`config/auth.php` 指向 `App\Models\User`），
> session 與 cache 都是檔案式。`laravel/laravel` 骨架帶來的這三個 migration 與
> `UserFactory` 已移除——它們建立的表應用從不使用，且在生產環境會直接建立空表。

### 5.2 生產

生產資料庫結構來自舊系統，**沒有**以 migration 管理。結構變更請以
`deploy/sql/` 底下的編號 SQL 檔案記錄並手動執行：

```bash
docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys \
  < deploy/sql/03_你的變更.sql
```

**執行前務必先備份**（見下節）。

現有腳本：

| 檔案 | 內容 |
|---|---|
| `deploy/sql/00_schema.sql` | 完整結構（30 張表，無資料），用於全新安裝 |
| `deploy/sql/01_schema_fix_utf8mb4.sql` | `tbl_member.modify_date` 零日期修正、`tbl_user.password` 加長至 255、`SET SESSION sql_mode=''` |
| `deploy/sql/02_laravel_framework_tables.sql` | `cache` / `cache_locks` / `jobs` / `failed_jobs` |

---

## 6. 備份與還原

### 備份

```bash
# 完整備份（含結構與資料）
docker compose exec -T mysql mysqldump -uroot -p"$DB_ROOT_PASSWORD" \
  --single-transaction --routines --triggers churchsys \
  | gzip > churchsys_$(date +%Y%m%d_%H%M%S).sql.gz

# 只備結構
docker compose exec -T mysql mysqldump -uroot -p"$DB_ROOT_PASSWORD" \
  --no-data churchsys > schema_$(date +%F).sql
```

`churchsys` 資料庫約 220 MB（gzip 後約 3–5 MB），備份很快。

> 舊系統的 `vw_user` 檢視因 definer 帳號不存在而無法 dump，遷移時已將該檢視移除，
> 現在的 dump **不需要** `--ignore-table` 參數。

### 還原

```bash
gunzip -c churchsys_backup.sql.gz | \
  docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys
```

還原會覆蓋現有資料，請先在另一台機器或另一個資料庫名稱驗證。

### 自動備份

目前**沒有**自動備份排程。建議在 `crontab` 加入每日備份，並將檔案同步至
S3 或另一個位置。詳見 [`DEPLOYMENT.md`](DEPLOYMENT.md)。

---

## 7. 字元集注意事項

大部分表已是 `utf8mb4` / `utf8mb4_unicode_ci`，但以下遺留表仍是 `utf8mb3`：

- `tbl_member_relationship`
- `tbl_worship_greeting`
- `tbl_worship_remarks`
- `AuthItem`、`AuthItemChild`、`AuthAssignment`、`Rights`

這些表目前皆為空或未被應用讀寫，暫無影響。若日後要啟用，需先轉換為 `utf8mb4`
（`utf8mb3` 無法儲存 emoji 與部分罕見漢字）。
