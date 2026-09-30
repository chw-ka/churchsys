# 給 Claude 的接手 Prompt

把下方程式碼區塊內的全文複製，貼給 Claude（Claude Cowork / Claude Code 皆可），
並把 `【】` 內的佔位資料替換成系統擁有者提供的實際內容。

---

```text
你現在接手管理 ChurchSys（教會會友管理系統），這是一套正在生產環境運作的
Laravel 系統。請嚴格按以下步驟進行，未完成上手前不要修改任何程式碼。

【背景】
- 儲存庫（public，clone 不需要 GitHub 帳號）：
  https://github.com/chw-ka/churchsys
- 生產伺服器：AWS Lightsail 18.143.26.232（Ubuntu 24.04，Docker Compose 三容器）
- 生產網址：https://churchsys.cmals.org
- 資料庫內有 2007 年至今的真實會友資料（約 4,700 人、22 萬筆出席紀錄），
  任何操作失誤都可能令教會損失資料。

【第一步：建立本機環境】
1. git clone https://github.com/chw-ka/churchsys.git churchsys && cd churchsys
2. 完整閱讀 CLAUDE.md（專案操作手冊，必讀），再讀 HANDOVER.md 與 docs/ 下四份文檔
   （ARCHITECTURE / DATABASE / DEPLOYMENT / TROUBLESHOOTING）。
3. 我會另外提供生產 .env 檔。收到後放到專案根目錄，確認 APP_TIMEZONE=Asia/Hong_Kong。
4. docker compose up -d --build，然後執行測試，應見 39 passed / 174 assertions。

【第二步：確認你已吸收的關鍵規則】
閱讀後請用自己的話向我簡述以下各點，確認理解無誤：
1. 為何所有 DATETIME 都是香港本地時間、絕不可把時區改回 UTC。
2. 為何 php artisan migrate 在生產必須是空操作、什麼情況下才可新增 migration。
3. 為何測試絕不可連上生產資料庫、防護機制在哪裡。
4. 前端為何沒有建置步驟、改版面應該改哪裡。
5. 八個報告頁的 SQL 為何不可擅自改寫。

【安全規則（違反即停）】
- 絕不提交 .env、*.pem、*.key、任何金鑰或密碼。
- 絕不提交任何含真實會友個資的檔案或截圖——儲存庫是 public 的，
  受香港《個人資料（私隱）條例》約束。
- 生產資料庫只準 SELECT；任何 INSERT/UPDATE/DELETE 必須先向我確認。
- 絕不執行 migrate:fresh、db:wipe、docker compose down -v、rm -rf、
  git push --force、git reset --hard（本機未提交的修改除外）。
- 部署到生產前必須先通過本機測試，且逐項向我報告將執行的步驟。

【日常維運（不需要改程式碼時）】
- 狀態：docker compose ps、docker compose logs --tail=100 app
- 部署：在伺服器 /home/ubuntu/churchsys 執行 git pull，再
  docker compose restart app（非必要不重啟 web/mysql）
- 備份：docker compose exec mysql mysqldump（指令見 docs/DEPLOYMENT.md §7）

【待辦（依優先次序，動手前先向我提出計劃）】
1. cmals.org 與 www.cmals.org 尚未轉址至 cmals.org.hk；在完成前
   絕不可建議關閉舊 EC2（54.169.156.17）。
2. 伺服器尚未設定自動備份與 swap（docs/DEPLOYMENT.md 有建議做法）。
3. CLAUDE.md §13 的已知缺陷（刪除出席紀錄損壞、簽到顯示 00:00 等）。

【SSH 存取】
SSH 私鑰：【請填入金鑰檔案路徑，例如 ~/.ssh/churchsys.pem】
登入方式：ssh -i <金鑰路徑> ubuntu@18.143.26.232

請先執行【第一步】，完成後回報每一步的實際輸出，再等我指示。
```

---

> **提醒**：`.env` 與 SSH 金鑰請用安全渠道（當面、Signal、一次性連結）傳送，
> 不要貼在對話或電郵內。
