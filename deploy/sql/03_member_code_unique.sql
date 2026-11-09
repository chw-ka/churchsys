-- ChurchSys schema change: enforce unique member codes
-- 2026-11-09
--
-- 背景：tbl_member.code 一直沒有任何唯一約束，歷史資料累積了 70 組重複
-- （同一人重複登記，以及兩個不同人共用同一編號）。重複編號會令簽到時
-- 以編號查會友出現錯配。已於 2026-11-09 清理歷史資料（見
-- deploy/fix-duplicate-codes-2026-11-09.php），此後以
-- STORED generated column + unique index 在資料庫層強制唯一。
--
-- 為何不直接 UNIQUE(code)：軟刪除會友的 code 會清空為 ''（歷史上已有
-- 800+ 筆，將來仍會繼續累積），普通 UNIQUE 不允許多筆 ''。
-- 因此用 generated column：code 非空時 = code；空時 = NULL。
-- InnoDB 的 UNIQUE 索引容許多個 NULL，所以已刪除的會友不會互相衝突，
-- 而真實編號則強制唯一。應用程式不需要讀寫此欄。
--
-- 註：不可寫成 CONCAT('#', id)——MySQL 不准 generated column 引用
-- auto_increment 欄位（ERROR 3109）。
--
-- 執行前檢查（必須回傳 0 行）：
--   SELECT code, COUNT(*) FROM tbl_member WHERE code <> ''
--   GROUP BY code HAVING COUNT(*) > 1;
--
-- 執行（備份後）：
--   docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASSWORD" churchsys \
--     < deploy/sql/03_member_code_unique.sql
--
-- 回滾：
--   ALTER TABLE tbl_member DROP KEY uq_member_code_uq, DROP COLUMN code_uq;

ALTER TABLE `tbl_member`
  ADD COLUMN `code_uq` VARCHAR(10) GENERATED ALWAYS AS (IF(`code` = '', NULL, `code`)) STORED,
  ADD UNIQUE KEY `uq_member_code_uq` (`code_uq`);
