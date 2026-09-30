# 疑難排解

> 相關文件：[`../CLAUDE.md`](../CLAUDE.md)、[`DATABASE.md`](DATABASE.md)、[`DEPLOYMENT.md`](DEPLOYMENT.md)
>
> 本檔案記錄已實際發生過的問題。遇到新問題並解決後，**請在此新增一節**。

---

## 快速排查流程

```bash
# 1. 網站有沒有回應
curl -sS -o /dev/null -w '%{http_code}\n' https://churchsys.cmals.org/login

# 2. 容器在不在
cd /home/ubuntu/churchsys && docker compose ps

# 3. 應用日誌
docker compose exec app tail -100 storage/logs/laravel.log

# 4. PHP 日誌
docker compose logs --tail=100 app

# 5. nginx 日誌
docker compose logs --tail=100 web
```

---

## 1. ★ 補加出席時，中文姓名全部「找不到」

**症狀**：在「補加出席」貼上一批中文姓名，結果全部落入「找不到」清單，但資料庫明明有
這些會友。用會友編號則正常。

**成因**：`preg_split` 缺少 `/u` 修飾符。沒有 `/u` 時，PCRE 以 byte 為單位處理，
中文字元（UTF-8 每字 3 bytes）會被切碎成無效位元組序列，比對必然失敗。

**修正**：

```php
// 錯誤
$token = trim(preg_split("/[\t,，;；|]+/", $line)[0] ?? $line);

// 正確 —— 必須有 /u
$token = trim(preg_split("/[\t,，;；|]+/u", $line)[0] ?? $line);
```

位置：`app/Http/Controllers/WorshipAttendanceController.php::adminStore()`。
**任何處理中文輸入的 `preg_*` 都應加 `/u`。**

---

## 2. ★ 新簽到的時間比實際時間早 8 小時

**症狀**：在崇拜現場簽到後，資料庫內的 `attendance_date` 比實際香港時間早 8 小時。
若在早上 08:00 前簽到，日期甚至會變成前一天，導致當日統計為 0。

**成因**：`config/app.php` 的 `timezone` 被設為 `UTC`，而資料庫內所有歷史時間戳
都是香港本地時間（由舊系統寫入）。

**診斷**（標準手法）：

```sql
SELECT DATE(attendance_date) AS d,
       TIME(MIN(attendance_date)) AS first_t,
       TIME(MAX(attendance_date)) AS last_t,
       COUNT(*) AS n
FROM tbl_worship_attendance
WHERE attendance_date >= '2026-09-01'
GROUP BY d ORDER BY d;
```

正常結果：主日落在 **08:00–12:00**、週六落在 **16:00–18:00**，與 `tbl_worship`
定義的崇拜時間吻合。若看到 00:00–04:00 的紀錄，就是時區被改成 UTC。

**修正**：`config/app.php`

```php
'timezone' => env('APP_TIMEZONE', 'Asia/Hong_Kong'),
```

`.env` 補上 `APP_TIMEZONE=Asia/Hong_Kong`，然後
`docker compose exec app php artisan config:clear`。

> 此問題在 2026-09-30 修復。修復前的 UTC 期間沒有寫入任何出席紀錄
> （已用小時分布驗證），所以**沒有需要更正的历史資料**。

---

## 3. 崇拜出席週報表：主日各堂全部顯示 0

**症狀**：教會反映「主日第一、二堂及宣道園崇拜的出席紀錄遺失」。崇拜出席資料頁面
每週只有週六崇拜有數字，主日三堂都是 0。

**這不是資料遺失。** 資料庫內紀錄完整（已逐日逐堂與舊 EC2 核對一致）。

**成因**：`listByWorship()` 舊寫法以「該週**最早**有簽到的那一天」（`$row->week_start`）
作為計數基準。週六崇拜的簽到時間最先出現，所以每一週都被判定為星期六，
主日（星期日）的簽到全部落在區間之外。附帶問題是 view 內每行執行一次查詢（N+1），
30 行等於 30 次查詢。

**修正**（已完成）：改為**單一聚合查詢** + PHP 端以 Carbon `isoWeekYear` / `isoWeek`
分組，並用 `LengthAwarePaginator` 分頁 30 週。因為不再使用資料庫專屬的週函式，
MySQL 與 SQLite 行為一致，可以寫測試。

- 程式：`WorshipAttendanceController::listByWorship()`
- 視圖：`resources/views/worship/list_by_worship.blade.php`（讀 `$row->counts`）
- 測試：`tests/Feature/WorshipByWorshipReportTest.php`
- 日期欄應顯示週區間（例：`2026-09-26 ~ 2026-09-27`），不是單一日期

**教訓**：涉及「週」的統計不要用 `MIN(date)` 當作區間代表值，要用 ISO 週。

---

## 4. POST 一律回 419 Page Expired

**症狀**：以 curl / 腳本測試時，所有 POST 都回 419。

**先確認這是不是應用的問題。** 實務經驗：419 幾乎都是**測試腳本**的問題，不是應用。

**成因與檢查**：

1. **沒有帶 CSRF token。** 表單 POST 需帶 `_token`，AJAX 需帶 `X-CSRF-TOKEN` 標頭。
2. **cookie jar 不一致。** 取得登入頁時拿到的 session cookie 必須在後續 POST 沿用。
   ```bash
   curl -c jar.txt -b jar.txt https://churchsys.cmals.org/login          # 取 token 與 cookie
   TOKEN=$(grep -o 'name="_token" value="[^"]*"' page.html | head -1 | sed 's/.*value="//;s/"//')
   curl -c jar.txt -b jar.txt -X POST ... -d "_token=$TOKEN&..."
   ```
3. **shell 吃掉了特殊字元。** bcrypt 雜湊含 `$`，在 shell 內會被展開。用單引號或
   改用檔案。
4. **`entries<@file` 語法錯誤。** curl 的 `-d @file` 讀整個 body，要帶欄位名時
   應使用 `--data-urlencode "entries@file"`。
5. **token 過期。** session 壽命 120 分鐘。

Session cookie 名稱為 `churchsys-session`（由 `config/session.php` 依 `APP_NAME` 生成）。

---

## 5. `tbl_member.modify_date` 出現 `0000-00-00 00:00:00`

**症狀**：遷移後新增或更新會友時，MySQL 報 `Incorrect datetime value`。

**成因**：舊 MySQL 5.7 允許 zero-date，MySQL 8 預設 `sql_mode` 不允許。

**修正**：已於 `deploy/sql/01_schema_fix_utf8mb4.sql` 處理：

```sql
SET SESSION sql_mode = '';
UPDATE tbl_member SET modify_date = '1970-01-01 00:00:01' WHERE modify_date = '0000-00-00 00:00:00';
ALTER TABLE tbl_member MODIFY modify_date TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';
```

另需將 `tbl_user.password` 由 `VARCHAR(60)` 加長至 `VARCHAR(255)`
（bcrypt 為 60 字元，加長是為了未來演算法升級）。

---

## 6. 匯出資料庫時 `vw_user` 出錯

**症狀**：`mysqldump` 報錯，提到 `The user specified as a definer ('church@localhost') does not exist`。

**成因**：舊系統的檢視 `vw_user` 由已不存在的帳號定義。

**修正**：遷移時已將該檢視移除，現在的 dump **不需要** `--ignore-table=churchsys.vw_user`。
若在其他舊環境遇到，可用：

```bash
mysqldump ... --ignore-table=churchsys.vw_user churchsys
```

---

## 7. 用 MySQL 命令列查中文顯示 `???`

**症狀**：查詢會友姓名得到一堆 `???`。

**這不是資料損壞**，是命令列客戶端的字元集問題。

**修正**：

```bash
docker compose exec mysql mysql -uroot -p"$DB_ROOT_PASSWORD" \
  --default-character-set=utf8mb4 churchsys -e "SELECT name FROM tbl_member LIMIT 5;"
```

---

## 8. 手機版選單仍會彈出 hover 子選單

**症狀**：響應式改造後，手機上點選單仍會展開舊式 hover 子選單，漢堡選單行為異常。

**成因**：`app-responsive.js` 帶著 `defer`，會在 jQuery 的 `ready` 之前執行；
之後 `jqueryslidemenu` 在 `ready` 時重新綁定 hover handler，把我們的解綁覆蓋掉。

**修正**：解綁動作必須延後到 jQuery `ready` 之後執行。

```js
setTimeout(function () { /* 解除 hover 綁定 */ }, 0);
```

**相關陷阱**：舊選單的子層有 **inline** `display:none` 與 `width`，
以 CSS 覆蓋時必須加 `!important`，否則 inline style 優先。

---

## 9. 手機瀏覽器無法安裝 PWA / service worker 未註冊

**症狀**：`manifest.json` 無法被辨識、service worker 註冊失敗。

**成因**：nginx 的 `mime.types` 已把 `.json` 對應到 `application/json`，
而 PWA 需要 `application/manifest+json`；`sw.js` 若被長期快取，更新不會生效。

**修正**：`deploy/nginx/default.conf` 內必須有這兩個 location：

```nginx
location = /sw.js {
    default_type application/javascript;
    add_header Cache-Control "no-cache";
    try_files $uri =404;
}

location = /manifest.json {
    types { }                                   # 清空繼承的 mime map
    default_type application/manifest+json;
    add_header Cache-Control "public, max-age=3600";
    try_files $uri =404;
}
```

修改 nginx 設定後：

```bash
docker compose exec web nginx -t          # 檢查語法
docker compose exec web nginx -s reload
```

另外，`sw.js` 對 HTML 採 **network-only**（不快取），這是刻意的：
若快取了需要 CSRF token 的頁面，使用者會拿到過期的 token 而持續 419。

---

## 10. 響應式改動後桌面版版面走樣

**成因**：新的 responsive 規則沒有包在 media query 內，或 `responsive.css`
被放在舊 CSS 之前。

**檢查**：

1. `public/css/responsive.css` 內**所有**規則都必須包在 `@media (max-width: ...)` 之內。
2. 在 `layouts/app.blade.php` 內，`responsive.css` 必須排在**所有舊 CSS 之後**。

斷點：`1024px` / `767px` / `400px`。

---

## 11. 容器被 OOM Kill

**症狀**：`docker compose ps` 顯示容器不斷重啟，`docker inspect <container>`
可見 `OOMKilled: true`。

**成因**：主機只有 1.9 GB 記憶體且**沒有 swap**，MySQL 8 本身佔用較多。

**修正**：加 swap（見 [`DEPLOYMENT.md`](DEPLOYMENT.md) §2），並確認
`docker/mysql/my.cnf` 的 `innodb_buffer_pool_size = 256M` 未被調高。

---

## 12. 瀏覽器顯示憑證錯誤

### `cmals.org` 顯示憑證已過期（`tommakdesign.com`）

**成因**：`cmals.org` 的 A 記錄仍指向舊 EC2 `54.169.156.17`，該機器上的憑證
在 2025-03-28 已到期。

**修正**：在 GoDaddy 將 `@` 與 `www` 設為 forwarding 至 <https://cmals.org.hk>。
詳見 [`DEPLOYMENT.md`](DEPLOYMENT.md) §5。

### `test.cmals.org` 顯示憑證名稱不符

**成因**：`test.cmals.org` 與生產指向同一台 Lightsail，但憑證只涵蓋
`churchsys.cmals.org`。

**修正**：刪除該 DNS 記錄，或為它單獨簽發憑證並加 nginx vhost。

### `churchsys.cmals.org` 憑證過期

```bash
sudo certbot certificates
sudo certbot renew --dry-run
sudo certbot renew --force-renewal
sudo /etc/letsencrypt/renewal-hooks/deploy/churchsys-copy.sh
```

---

## 13. 本地沒有瀏覽器可做視覺驗證

**情境**：需要確認響應式版面在手機 / iPad / 桌面是否正常，但環境沒有可用的
Chrome/Chromium（CDN 下載逾時）。

**做法**：使用 `playwright-core`（不安裝瀏覽器），指向系統既有的 Chrome for Testing：

```js
const { chromium } = require('playwright-core');
const browser = await chromium.launch({
  executablePath: process.env.HOME + '/Library/Caches/ms-playwright/chromium-1200/chrome-mac/Chromium.app/Contents/MacOS/Chromium'
});
```

以 375（手機）/ 768（iPad）/ 1440（桌面）三種 viewport 截圖比對。

---

## 14. 測試失敗：`ExampleTest`

**症狀**：`php artisan test` 有一個失敗，指向 `tests/Feature/ExampleTest.php`，
原因是 `/` 回傳 302 而非 200。

**這是預期的。** `/` 需要登入，未登入時會重導至 `/login`。可以忽略，
或把該測試改成 `assertRedirect(route('login'))`。

---

## 15. 資料庫函式在測試中報錯

**症狀**：在 SQLite 測試中執行報告頁，出現 `no such function: YEAR` 之類的錯誤。

**成因**：八個報告頁使用 MySQL 專屬函式（`YEAR()`、`WEEKOFYEAR()`、`DAYOFYEAR()`、
`DATE_FORMAT()`）。測試環境是 SQLite。

**做法**：這些頁面**不要**寫 SQLite 測試，改在 MySQL 上驗證。
若確實需要納入測試，需先把 SQL 改寫為跨資料庫相容的形式
（可參考 `listByWorship()` 的做法）。

---

## 附錄：新增問題的格式

回答以下幾點，讓下一個人不必重新摸索：

```markdown
## N. 一句話描述症狀

**症狀**：使用者看到什麼。
**成因**：技術根因。
**診斷**：用什麼指令或查詢確認。
**修正**：改了哪個檔案、哪一行、改成什麼。
**預防**：如何避免再犯。
```
