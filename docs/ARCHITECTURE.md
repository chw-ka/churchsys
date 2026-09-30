# 架構

> 相關文件：[`../CLAUDE.md`](../CLAUDE.md)、[`DATABASE.md`](DATABASE.md)、[`DEPLOYMENT.md`](DEPLOYMENT.md)

---

## 1. 系統全貌

```
                         Internet
                            │
                  https://churchsys.cmals.org
                            │
                ┌───────────▼───────────┐
                │  AWS Lightsail        │
                │  18.143.26.232        │
                │  Ubuntu 24.04         │
                │                       │
                │  ┌─────────────────┐  │
                │  │ churchsys-web   │  │  nginx:alpine，對外 80 / 443
                │  │ (nginx)         │  │  TLS 憑證由 certbot 寫入並掛載
                │  └────────┬────────┘  │
                │           │ fastcgi   │
                │  ┌────────▼────────┐  │
                │  │ churchsys-app   │  │  php:8.3-fpm，無對外埠
                │  │ (php-fpm)       │  │
                │  └────────┬────────┘  │
                │           │ pdo_mysql │
                │  ┌────────▼────────┐  │
                │  │ churchsys-mysql │  │  mysql:8.0，僅綁 127.0.0.1:3306
                │  │ volume: mysql_data│ │  資料持久化於 Docker volume
                │  └─────────────────┘  │
                └───────────────────────┘
                            │
                   s3fs 掛載（唯讀）
                            │
              S3 bucket → /mnt/s3-drive/churchsys/file
                            │
                     掛進容器 → public/storage/file
                    （會友相片，由舊 EC2 沿用至今）
```

三個容器由 `docker-compose.yml` 定義，位於同一 bridge 網絡 `churchsys-net`。
`app` 與 `web` 都把專案目錄 bind mount 進容器，所以 `git pull` 後不需要重建映像
（除非 `Dockerfile` 或 PHP 擴充有變動）。

---

## 2. 請求生命週期

1. 瀏覽器 → nginx（80）→ 301 轉 https。
2. nginx（443）靜態檔案優先：`/css`、`/js`、`/images`、`/icons`、`/storage/file`
   直接由 `public/` 回傳。
3. 其餘路徑 → `public/index.php`（Laravel front controller）。
4. nginx 以 fastcgi 交給 `app:9000`。
5. Laravel 經 `routes/web.php` 分派，先過 `auth` middleware（`tbl_user` session）。
6. Session 存於檔案（`storage/framework/sessions`），cookie 名為 `churchsys-session`。

`/sw.js` 與 `/manifest.json` 有專屬 location（見 `deploy/nginx/default.conf`），
因為 `.json` 的 MIME type 需要強制覆寫，而 service worker 必須設 `no-cache`。

---

## 3. 程式碼分層

專案採 Laravel 的標準 MVC，沒有 Repository / Service 層，Controller 直接操作 Eloquent
或原生 SQL。**維持這個風格即可，不需要引入額外抽象。**

```
routes/web.php
   │
   ├─ AuthController ─────────► views/auth/*
   ├─ DashboardController ────► views/dashboard
   ├─ MemberController ───────► views/members/*
   ├─ WorshipController ──────► views/worship/admin|form
   ├─ WorshipAttendanceController ──► views/worship/take|admin_take|list_by_*
   └─ WorshipReportController ─────► views/worship/report-index|reports/*
                                        │
                                   Models（5 個）
                                        │
                     MySQL：tbl_member / tbl_worship /
                            tbl_worship_attendance / tbl_user /
                            tbl_group_period
```

---

## 4. 路由表

### 訪客

| 方法 | 路徑 | 名稱 | 動作 |
|---|---|---|---|
| GET | `/login` | `login` | 登入頁 |
| POST | `/login` | — | 處理登入 |

### 已登入

| 方法 | 路徑 | 名稱 | 說明 |
|---|---|---|---|
| GET | `/` | `dashboard` | 首頁統計 |
| POST | `/logout` | `logout` | 登出 |
| POST | `/password/update` | `password.update` | 更新密碼 |
| GET | `/members` | `members.index` | 會友列表（每頁 30，可搜尋） |
| GET | `/members/create` | `members.create` | 新增會友 |
| GET | `/members/duplicate` | `members.duplicate` | 重複姓名檢查 |
| GET | `/members/update-account` | `members.update-account` | 修改自己帳號 |
| GET | `/members/autocomplete` | `members.autocomplete` | 自動完成（JSON） |
| POST | `/members` | `members.store` | 建立會友 |
| GET | `/members/{member}` | `members.show` | 會友詳情 |
| GET | `/members/{member}/edit` | `members.edit` | 編輯會友 |
| PUT | `/members/{member}` | `members.update` | 更新會友 |
| DELETE | `/members/{member}` | `members.destroy` | 軟刪除 |
| GET | `/worship` | `worship.admin` | 崇拜場次管理 |
| GET | `/worship/create` | `worship.create` | 新增崇拜 |
| POST | `/worship` | `worship.store` | 建立崇拜 |
| GET | `/worship/{worship}/edit` | `worship.edit` | 編輯崇拜 |
| PUT | `/worship/{worship}` | `worship.update` | 更新崇拜 |
| GET | `/worship/report` | `worship.report` | 報告索引 |
| GET | `/worship/report/new-member-attendance` | `…new-member-attendance` | 新朋友出席 |
| GET | `/worship/report/new-membership-card` | `…new-membership-card` | 新會友證 |
| GET | `/worship/report/weekly-new-member` | `…weekly-new-member` | 每週新朋友 |
| GET | `/worship/report/attendance` | `…attendance` | 出席統計 |
| GET | `/worship/report/absent` | `…absent` | 缺席名單 |
| GET | `/worship/report/annual` | `…annual` | 年度統計 |
| GET | `/worship/report/raw` | `…raw` | 原始資料 |
| GET | `/worship/report/birthday` | `…birthday` | 生日名單 |
| GET | `/worship/attendance/take` | `worship.take` | ★ 簽到畫面 |
| POST | `/worship/attendance/checkin` | `worship.attendance.checkin` | ★ 簽到（AJAX） |
| GET | `/worship/attendance/admin-take` | `worship.attendance.admin-take` | ★ 補加出席 |
| POST | `/worship/attendance/admin-take` | `worship.attendance.admin-store` | ★ 處理補加 |
| GET | `/worship/attendance/by-member` | `worship.attendance.by-member` | 會友出席資料 |
| GET | `/worship/attendance/by-worship` | `worship.attendance.by-worship` | 崇拜出席資料 |
| DELETE | `/worship/attendance/{id}` | `worship.attendance.destroy` | 刪除單筆（**損壞，見 §6**） |

> **排序規則**：所有靜態路徑必須寫在 `{member}` / `{worship}` 動態路徑之前，
> 否則會被攔截。新增路由時務必遵守。

---

## 5. 三個核心流程

### 5.1 即時簽到

```
take.blade.php（jQuery，選崇拜 → 輸入會友編號）
        │  POST /worship/attendance/checkin  (X-CSRF-TOKEN)
        ▼
WorshipAttendanceController::checkin()
        │  Member::where('code')->where('state',1)->first()
        │  同日同崇拜是否已存在？
        ▼
  400/409 → 回傳失敗訊息
  成功   → WorshipAttendance::create() → 回傳 JSON（含當日名單 HTML）
```

### 5.2 補加出席（整批）

```
admin_take.blade.php（選崇拜 + 日期 + 貼上多行名單）
        │  POST /worship/attendance/admin-take
        ▼
WorshipAttendanceController::adminStore()
        │  逐行 preg_split("/[\t,，;；|]+/u") 取第一格
        │  先比 code，再比 name
        │  分類：added / duplicates / notFound / ambiguous
        ▼
  redirect → admin-take（結果經 session flash）
```

### 5.3 崇拜出席資料（週矩陣）

```
list_by_worship.blade.php
        │  GET /worship/attendance/by-worship?year=&page=
        ▼
WorshipAttendanceController::listByWorship()
        │  單一聚合查詢：SELECT DATE(attendance_date), worship_id, COUNT(*)
        │                  GROUP BY DATE, worship_id  （限指定年份）
        │  PHP 端以 Carbon isoWeekYear / isoWeek 砌成週矩陣
        ▼
  LengthAwarePaginator(30 週)，view 讀 $row->counts
```

> 這個方法刻意避開資料庫專屬的週函式，令 MySQL 與 SQLite 行為一致、可測試。
> 歷史教訓：舊寫法用「該週最早有簽到的那一天」去計數，令週六崇拜吃掉了整週，
> 主日三堂全部顯示 0，被誤認為資料遺失。

---

## 6. 已知的架構層面問題

| 問題 | 位置 | 影響 |
|---|---|---|
| 刪除出席紀錄損壞 | `WorshipAttendanceController::destroy()` | 用 `findOrFail($id)`，但表無 `id` 欄位 → 必然失敗 |
| 簽到時間顯示 `00:00` | `WorshipAttendance` 的 `'date'` cast | 名單時間欄永遠是 00:00 |
| 缺少 `Group` / `GroupAttendance` 模型 | `Member`、`GroupPeriod` | 關係方法未被呼叫，一旦使用會拋例外 |
| 無權限分層 | 全站 | 任何登入者可刪除會友 |
| 報告頁綁死 MySQL | `WorshipReportController` | 無法以 SQLite 測試 |

詳見 [`../CLAUDE.md`](../CLAUDE.md) §13。

---

## 7. 前端

無建置步驟。所有資源直接由 `public/` 提供。

| 檔案 | 用途 |
|---|---|
| `css/screen.css`, `print.css` | Blueprint CSS 框架 |
| `css/main.css`, `form.css`, `rounded.css`, `gridview.css` | 舊有版面樣式 |
| `css/takeAttendance.css` | 簽到頁專屬 |
| `css/responsive.css` | **新增的響應式覆蓋層**（斷點 1024 / 767 / 400） |
| `js/jquery.min.js` | jQuery 1.x |
| `js/jqueryslidemenu/` | 舊有下拉選單 |
| `js/app-responsive.js` | 表格捲動、漢堡選單、PWA 註冊與安裝提示 |
| `manifest.json`, `sw.js`, `offline.html`, `icons/` | PWA |

載入順序不可調換，理由見 [`../CLAUDE.md`](../CLAUDE.md) §10。
