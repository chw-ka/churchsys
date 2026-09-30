# ChurchSys（教會系統易）

教會會友管理與崇拜出席紀錄系統。以 Laravel 13 + MySQL 8 + Docker Compose 運行，
生產環境位於 AWS Lightsail，網址 <https://churchsys.cmals.org>。

資料庫由舊的 Yii 1.1 系統遷移而來，內含 2007 年至今的崇拜出席歷史紀錄。

---

## 功能範圍

| 模組 | 內容 |
|---|---|
| **簽到** | 會友以會友編號即時簽到，AJAX 回傳當日名單 |
| **補加出席** | 崇拜後整批補登，支援從試算表直接貼上（tab / 逗號分隔），自動分辨重複、找不到、同名多人 |
| **會友管理** | 新增／編輯／查詢／軟刪除、重複姓名檢查、相片上傳 |
| **崇拜管理** | 崇拜場次的新增與編輯（時間、星期、啟用狀態） |
| **出席查詢** | 依會友（2／6／12 個月出席統計）或依崇拜（ISO 週矩陣，分頁） |
| **報告** | 新朋友出席、新會友證、每週新朋友、出席統計、缺席名單、年度統計、原始資料、生日 |
| **PWA** | 可安裝至手機主畫面，基本離線提示 |
| **響應式** | 手機 / iPad / 桌面三種版面 |

---

## 快速開始

```bash
git clone <repo-url> churchsys && cd churchsys
cp .env.example .env          # 填入 DB_PASSWORD / DB_ROOT_PASSWORD

docker compose up -d --build
docker compose exec app php artisan key:generate

# 匯入資料庫結構（無資料）
docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys < deploy/sql/00_schema.sql
```

開啟 <http://localhost>。生產資料庫由系統擁有者提供，請勿自行匯入。

執行測試：

```bash
docker compose exec app php artisan test
```

---

## 技術摘要

| 項目 | 版本／說明 |
|---|---|
| PHP | 8.3（`php:8.3-fpm`） |
| Laravel | 13 |
| MySQL | 8.0，utf8mb4 |
| nginx | alpine，反向代理至 `app:9000` |
| 前端 | Blueprint CSS + jQuery，**無建置步驟**（勿引入 Vite） |
| 時區 | `Asia/Hong_Kong`（**不可改**） |

---

## 文件

| 檔案 | 內容 |
|---|---|
| [`CLAUDE.md`](CLAUDE.md) | **先讀這個。** agent 操作手冊：領域規則、關鍵不變量、禁區 |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | 架構、路由表、資料流 |
| [`docs/DATABASE.md`](docs/DATABASE.md) | 資料表結構、欄位語意、查詢範例 |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | 伺服器、Docker、SSL、DNS、備份 |
| [`docs/TROUBLESHOOTING.md`](docs/TROUBLESHOOTING.md) | 已踩過的坑與排查方法 |
| [`HANDOVER.md`](HANDOVER.md) | 交接清單：需要哪些憑證、如何取得 |

---

## 專案狀態

- 生產環境：Lightsail `18.143.26.232`，`/home/ubuntu/churchsys`
- 舊 EC2 環境（`54.169.156.17`，Yii 1.1）已完成完整備份，待停用
- 已知未修復問題見 `CLAUDE.md` §13
