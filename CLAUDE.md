# CLAUDE.md — ChurchSys 工作指引

本檔案是這個專案的操作手冊。任何 agent（Claude Code、Cursor、Copilot）在動手修改之前，
請先完整讀完本檔案。內容涵蓋專案定位、架構、領域規則、關鍵不變量、常用工作流程與禁區。

---

## 1. 專案定位

**ChurchSys（教會系統易）** 是為教會編寫的會友管理系統，屬實際使用中的生產系統。
日常有教會同工登入為會友簽到、查閱出席紀錄、產出週報／年報。

核心使用場景是**崇拜出席紀錄**：

- 會友在崇拜期間以會友編號簽到（`/worship/attendance/take`）。
- 崇拜後如漏簽，同工用「補加出席」頁面整批補回（可從試算表直接貼上）。
- 每週／每年產出各式報告（新朋友出席、缺席名單、生日、年度統計等）。

**這是一套「接手既有遺留資料」的系統**，不是全新開發。資料庫由舊的 Yii 1.1 系統
遷移過來，內含 2007 年至今的歷史紀錄。任何改動都必須先考慮既有資料的相容性。

---

## 2. 技術棧

| 層 | 技術 | 備註 |
|---|---|---|
| 框架 | Laravel 13（`laravel/framework ^13.17`） | `composer.json` |
| 語言 | PHP 8.3 | Docker `php:8.3-fpm` |
| 資料庫 | MySQL 8.0 | 由 MySQL 5.7 遷移而來，utf8mb4 |
| Web | nginx（alpine） | 反向代理至 `app:9000` |
| 容器 | Docker Compose，三個服務：`web` / `app` / `mysql` | `docker-compose.yml` |
| 前端 | **Blueprint CSS + jQuery，無建置步驟** | 見下方警告 |
| 認證 | Laravel session guard，`tbl_user` 表 | 兼容舊 MD5 密碼 |

> **前端沒有建置步驟。** `package.json` / `vite.config.js` / Tailwind 都是 Laravel 骨架
> 帶進來的樣板，實際上**完全沒有使用**：版型 `resources/views/layouts/app.blade.php`
> 沒有任何 `@vite()` 指令，樣式全部是 `public/css/*.css`（Blueprint CSS）與
> `public/js/*.js`（jQuery、jqueryslidemenu）。改版面請直接改 `public/` 底下的檔案。
> **不要執行 `npm run build`，也不要引入 Vite。** `composer setup` 腳本裡的
> `npm install && npm run build` 是骨架殘留，可以忽略。

---

## 3. 快速開始

### 3.1 本機開發（Docker）

```bash
git clone <repo-url> churchsys && cd churchsys
cp .env.example .env
# 向系統擁有者索取生產 .env，或自行填寫 DB_PASSWORD / DB_ROOT_PASSWORD
docker compose up -d --build
docker compose exec app php artisan key:generate   # 只在 APP_KEY 為空時

# 匯入資料庫結構（本機測試用；生產資料由擁有者提供）
docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys < deploy/sql/00_schema.sql
```

服務位置：`http://localhost`（nginx → app）。MySQL 在 `127.0.0.1:3306`（僅綁本機）。
若不想開 nginx，也可以 `docker compose exec app php artisan serve --host=0.0.0.0`。
> 注意：以 `artisan serve` 執行時，`.env` 的 `DB_HOST` 要改成 `127.0.0.1`。

### 3.2 執行測試

```bash
docker compose exec app php artisan test
```

測試使用 SQLite in-memory（見 `phpunit.xml`），schema 由兩個 migration 建立，
**不需要 MySQL**：

- `database/migrations/0001_01_01_000000_create_framework_tables.php` — framework 表，
  每張表都先檢查存在才建立
- `database/migrations/2026_09_29_000001_create_churchsys_tables.php` — 四張核心表

兩個 migration 都是**冪等**的，所以在任何環境執行 `php artisan migrate` 都安全。
撰寫新測試時請注意：MySQL 專屬函式（`YEAR()`、`WEEKOFYEAR()`、`DAYOFYEAR()`）
在 SQLite 不能執行。

`tests/Feature/ExampleTest.php` 預期失敗（`/` 對未登入使用者回 302），這是正常的。

---

## 4. 目錄與檔案地圖

```
app/Http/Controllers/
  AuthController.php              登入／登出／改密碼（含 MD5 → bcrypt 自動升級）
  DashboardController.php         首頁統計（四個數字）
  MemberController.php            會友 CRUD、重複姓名檢查、autocomplete
  WorshipController.php           崇拜定義（崇拜場次）的 CRUD
  WorshipAttendanceController.php ★ 簽到、補加出席、出席查詢（最核心）
  WorshipReportController.php     八個報告頁（全部用原生 SQL，見 §9）

app/Models/
  Member.php            tbl_member            state / gender / account_type 常數
  Worship.php           tbl_worship           weekly 0=週日 … 6=週六
  WorshipAttendance.php tbl_worship_attendance 複合主鍵 (worship_id, member_id, attendance_date)
  User.php              tbl_user              自訂 verifyPassword()，兼容 MD5
  GroupPeriod.php       tbl_group_period      僅報告頁用於分組期間

resources/views/
  layouts/app.blade.php        全站版型（選單、CSS/JS 載入順序）
  dashboard.blade.php          首頁
  auth/                        登入、改帳號
  members/                     會友列表／詳情／表單／重複名單
  worship/                     崇拜管理、簽到、補加出席、兩種出席查詢
  worship/reports/             八個報告頁

public/                         所有前端資源（無建置步驟）
  css/responsive.css            響應式覆蓋層（見 §10）
  js/app-responsive.js          表格捲動、漢堡選單、PWA 安裝與註冊
  manifest.json / sw.js / offline.html / icons/   PWA
  css/*.css  js/*.js            舊有 Blueprint CSS 與 jQuery

deploy/                         部署產物（伺服器實際使用）
  nginx/default.conf            nginx 站台設定
  nginx/ssl/                    TLS 憑證（由 certbot 寫入，不進版控）
  certbot/churchsys-copy.sh     Let's Encrypt 續期後複製憑證並重載 nginx
  sql/00_schema.sql             生產資料庫完整結構（30 張表，無資料）
  sql/01_schema_fix_utf8mb4.sql 歷史修正：utf8mb4 轉換、zero-date、密碼欄長度
  sql/02_laravel_framework_tables.sql  cache / cache_locks / jobs / failed_jobs

docs/                           深入文件（架構、資料庫、部署、疑難排解）
```

---

## 5. 領域規則（務必遵守）

### 5.1 會友（`tbl_member`）

| 欄位 | 意義 |
|---|---|
| `state` | `1` 有效、`0` 已刪除、`2` 已離世 |
| `code` | 會友編號，簽到時的識別碼。**刪除會友時會被清空為 `''`** |
| `name` | 中文姓名。**允許重名**，補加出席時重名會被列為 ambiguous |
| `account_type` | `0` 新朋友、`1` 會友、`2` 同工 |
| `gender` | `1` 女、`2` 男、`3` 未知 |

查詢一律經 `Member::active()` scope（等同 `state = 1`）。
刪除是**軟刪除**：`state` 改 0、`code` 清空、`account_type` 改 -1。

### 5.2 崇拜（`tbl_worship`）

`weekly` 是**星期幾**，不是「每週」的意思：`0` = 週日、`1` = 週一 … `6` = 週六。
現行有效的崇拜場次（`state = 1`）：

| id | 名稱 | weekly | 時間 |
|---|---|---|---|
| 1 | 主日第一堂 | 0 | 08:00–09:25 |
| 18 | 宣道園崇拜 | 0 | 09:30–10:25 |
| 3 | 主日第二堂 | 0 | 10:30–12:00 |
| 12 | 週六崇拜 | 6 | 16:00–18:00 |

其餘 `state = 0` 為停用或測試資料（多筆名稱叫 `testing`）。新增崇拜請設定 `state`。

### 5.3 出席（`tbl_worship_attendance`）

- **複合主鍵** `(worship_id, member_id, attendance_date)`，**沒有 `id` 欄位**。
  因此「同一崇拜 + 同一會友 + 同一時間戳」才算重複；實務上以 `whereDate()` 判斷同日。
- `attendance_date` 是 `DATETIME`，儲存**香港本地時間**（見 §6.1）。
- 有指向 `tbl_worship` / `tbl_member` 的外鍵，但沒有 CASCADE，刪除會友不會連帶刪除
  出席紀錄。

### 5.4 簽到流程

`checkin()`（AJAX）：以 `code` 查有效會友 → 檢查同日同崇拜是否已簽 → 新增 → 回傳 JSON
（含重新渲染的當日名單 HTML）。重複簽到回 409，找不到編號回 404。

### 5.5 補加出席（`adminTake` / `adminStore`）

- 日期上限為今天（`before_or_equal:today`）。
- 輸入支援試算表貼上：**逐行處理，每行只取第一格**，分隔符為 tab、半形／全形逗號、
  半形／全形分號、`|`。
- 先用 `code` 精確比對，找不到才用 `name` 精確比對；同名多於一人 → 列入 ambiguous。
- 已存在 → 列入 duplicates；找不到 → 列入 notFound。
- 採 PRG 模式：結果經 session flash 回傳，只有出現 notFound／ambiguous 時才回填輸入框。

---

## 6. 關鍵不變量（改壞了會靜默出錯）

### 6.1 ★ 時區必須是 `Asia/Hong_Kong`

`config/app.php` 的 `timezone` 設為 `env('APP_TIMEZONE', 'Asia/Hong_Kong')`。

原因：`tbl_worship_attendance` 內所有時間戳都是舊系統以香港時間寫入的。以
`tbl_worship` 的崇拜時間（主日第一堂 08:00）對照資料庫實際分布，出席紀錄集中在
08–12 時與 16–17 時，即香港本地時間。

若改回 UTC，`now()` 會比資料庫時間慢 8 小時，後果：

- 新簽到時間全部偏移 8 小時，與歷史資料不一致。
- 早上 08:00 前的簽到會**記到前一日**，直接污染日期統計。
- `whereDate('attendance_date', today())` 的「今日已簽到」判斷會錯。
- 補加出席的 `before_or_equal:today` 驗證會誤擋或誤放。

**不要把它改回 UTC。**

### 6.2 中文多字節：`preg_split` 必須加 `/u`

```php
// 正確
$token = trim(preg_split("/[\t,，;；|]+/u", $line)[0] ?? $line);
```

少了 `/u`，中文字元會被逐 byte 切開變成亂碼，補加出席會全部找不到會友。
這問題曾經在生產環境發生過。

### 6.3 CSRF cookie 名稱

Session cookie 名為 `churchsys-session`（由 `config/session.php` 依 `APP_NAME` 生成）。
以 curl／腳本測試時，POST 必須帶 `_token` 並沿用同一個 cookie jar，否則會拿到 419。
**419 幾乎都是測試腳本的問題，不是應用的問題。**

### 6.4 資料庫字元集

生產資料庫為 `utf8mb4` / `utf8mb4_unicode_ci`。以命令列查詢中文時請加
`--default-character-set=utf8mb4`，否則終端機會顯示 `???`（資料本身沒問題）。

### 6.5 時間戳欄位

- `tbl_member.modify_date` 在舊資料中有 `0000-00-00` 零日期，已於
  `deploy/sql/01_schema_fix_utf8mb4.sql` 修正為 `1970-01-01 00:00:01` 並放寬 `sql_mode`。
- `Member` / `Worship` / `WorshipAttendance` 三個模型都是 `public $timestamps = false`，
  時間欄位要自行設定（`create_date`、`modify_date`、`last_login_at` 等）。

---

## 7. 認證與權限

- 登入在 `tbl_user`（58 個帳號），**不是** Laravel 預設的 `users` 表。
  `config/auth.php` 已指向 `App\Models\User`。
- `User::verifyPassword()` 兼容舊 MD5 密碼：若偵測到 32 字元且非 bcrypt，
  比對成功後會**自動改寫為 bcrypt**。
- 目前**沒有角色／權限分層**——所有登入者可存取所有頁面。
  舊系統遺留的 `AuthItem` / `AuthAssignment` / `AuthItemChild` 表未被使用。
- 路由權限只有 `guest`（登入頁）與 `auth`（其餘全部）兩層，見 `routes/web.php`。

---

## 8. 路由慣例

`routes/web.php` 內**靜態路由必須排在動態路由之前**，否則 `{member}` / `{worship}`
會攔截 `/members/create`、`/worship/report` 等同層路徑。新增路由時請遵守這個排序。

命名慣例：`worship.attendance.*`、`worship.report.*`、`members.*`。

---

## 9. 報告頁

八個報告頁（`WorshipReportController`）**全部使用原生 `DB::select()` 搭配 MySQL 專屬
函式**（`YEAR()`、`WEEKOFYEAR()`、`DAYOFYEAR()`、`DATE_FORMAT()` 等），並直接回傳
`stdClass` 給 view。這是刻意保留舊 Yii 系統的 SQL 邏輯以確保數字與教會既有報告一致。

- 要改動報告數字時，**請先向教會核對**既有報告的定義，不要自行「優化」。
- 這些頁面無法用 SQLite 測試；驗證請在 MySQL 上進行。
- 每個報告 view 都包了 `.table-scroll` 容器以支援手機橫向捲動，新增報告頁請比照。

`WorshipAttendanceController::listByWorship`（崇拜出席資料）是例外——已改寫為
**單一聚合查詢 + PHP 端 ISO 週分組**，以避開原本的 N+1 與「只計最早一日」的錯誤，
並以 `LengthAwarePaginator` 分頁 30 週。

---

## 10. 響應式與 PWA

設計原則是**覆蓋，不是重寫**：舊有 Blueprint CSS 完全不動，新增
`public/css/responsive.css`（斷點 1024 / 767 / 400，全部規則包在 `max-width`
media query 內，桌面版零改動）與 `public/js/app-responsive.js`。

載入順序**不可調換**（見 `layouts/app.blade.php`）：

1. 舊 CSS
2. `responsive.css`（必須在最後）
3. jQuery、jqueryslidemenu
4. `app-responsive.js`（必須在舊選單 script 之後，且用 `defer`）

改動 responsive 時的三個陷阱：

- `defer` script 會在 jQuery `ready` 之前執行，解除舊選單的 hover 綁定要用
  `setTimeout(0)` 延後，否則手機上會重新綁回。
- 舊選單子層有 inline `display:none` / `width`，覆蓋時必須用 `!important`。
- 表格橫向捲動由 `.table-scroll` 包裝建立，凍結欄用 `data-freeze-cols="N"`。

PWA 部分：`manifest.json` 為 standalone、`sw.js` 對 HTML 採 network-only（避免快取
到需要 CSRF 的頁面）、靜態資源採 stale-while-revalidate。nginx 需為 `/sw.js`、
`/manifest.json`、`/icons` 設專屬 location（已寫在 `deploy/nginx/default.conf`）。

---

## 11. 部署

生產環境：AWS Lightsail（`18.143.26.232`），Ubuntu 24.04，路徑 `/home/ubuntu/churchsys`，
以 Docker Compose 執行。網域 `https://churchsys.cmals.org`。

```bash
ssh -i <key>.pem ubuntu@18.143.26.232
cd /home/ubuntu/churchsys
git pull --ff-only
docker compose up -d --build          # 僅在 Dockerfile / 依賴有變動時需要
docker compose exec app php artisan config:clear
docker compose exec app php artisan view:clear
docker compose exec app php artisan migrate --force   # 安全：兩個 migration 都是冪等的
```

更新後請清 `config` 與 `view` 快取；**不要**在生產執行 `php artisan optimize`。

> **關於 `migrate`**：本專案的 migration 只有兩個，且都先檢查資料表是否存在才建立，
> 因此在生產環境執行是空操作。生產資料庫結構本身來自舊系統，並非由 migration 管理
> ——結構變更請以 `deploy/sql/` 底下的編號 SQL 檔案記錄（見 `docs/DATABASE.md` §5）。

> **本專案沒有 Laravel 預設的 `users`、`password_reset_tokens`、`sessions` 表。**
> 認證讀 `tbl_user`，session 與 cache 都是檔案式。這是刻意的，請勿「補回」。

完整部署、SSL、DNS、備份說明見 `docs/DEPLOYMENT.md`。

---

## 12. 資料庫存取

```bash
# 進入 MySQL（密碼見 .env 的 DB_ROOT_PASSWORD）
docker compose exec mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys

# 以 utf8mb4 查中文
docker compose exec mysql mysql -uroot -p"$DB_ROOT_PASSWORD" \
  --default-character-set=utf8mb4 churchsys -e "SELECT id, code, name FROM tbl_member LIMIT 5;"

# 備份（出席紀錄約 22 萬筆，成員約 4,500 筆）
docker compose exec -T mysql mysqldump -uroot -p"$DB_ROOT_PASSWORD" \
  --single-transaction --routines --triggers churchsys | gzip > backup_$(date +%F).sql.gz
```

**動生產資料庫之前一定要先備份。** 出席紀錄是無可取代的教會歷史資料。

---

## 13. 已知問題（尚未修復）

修正前請先與系統擁有者確認預期行為。

1. **`WorshipAttendanceController::destroy()` 無法運作。**
   `tbl_worship_attendance` 沒有 `id` 欄位，但程式用 `findOrFail($id)` 與
   `DELETE /worship/attendance/{id}` 路由。需要改用複合鍵刪除。

2. **簽到後的名單時間永遠顯示 `00:00`。**
   `WorshipAttendance` 把 `attendance_date` cast 成 `'date'`，時間部分被截掉，
   而 `checkin()` 卻用 `format('H:i')` 輸出。需要改為 `'datetime'` cast。

3. **模型引用了不存在的類別。**
   `Member::groups()`、`Member::groupAttendances()`、`GroupPeriod::groups()` 指向
   `Group` / `GroupAttendance` 模型，但這些類別並不存在。
   目前沒有呼叫端，但一旦使用就會拋 `Class not found`。

4. **沒有角色權限分層**（見 §7）。任何登入者都能刪除會友。

5. **`CACHE_STORE` / `QUEUE_CONNECTION` 與生產 `.env` 不一致。**
   生產 `.env` 仍寫著舊的 `CACHE_DRIVER=file` 與 `QUEUE_DRIVER=sync`，這兩個鍵在
   Laravel 13 已不生效，實際會回落到 `database`。應用沒有佇列工作，影響有限，
   但建議統一改為 `CACHE_STORE=file` / `QUEUE_CONNECTION=sync`。

---

## 14. 禁區

- **不要提交真實 `.env`、`.pem`、TLS 私鑰。** `.gitignore` 已封鎖，請勿繞過。
- **不要直接 `UPDATE` / `DELETE` 生產資料庫**，除非已備份且使用者明確要求。
- **不要把時區改回 UTC**（§6.1）。
- **不要移除 `preg_split` 的 `/u`**（§6.2）。
- **不要引入 Vite / Tailwind 建置流程**（§2）。
- **不要重寫八個報告頁的原生 SQL**（§9）。
- **不要調整 `layouts/app.blade.php` 的 CSS/JS 載入順序**（§10）。
- 改動前後都應在 `docs/TROUBLESHOOTING.md` 記錄新發現的坑。
