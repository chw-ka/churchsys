-- ChurchSys schema migration fix: MySQL 5.7 -> 8.0 + utf8mb4 (per UPGRADE_PLAN step C)
SET FOREIGN_KEY_CHECKS = 0;
-- allow legacy zero-dates during conversion (preserves data as-is)
SET SESSION sql_mode = '';

-- 0. fix zero-date default FIRST (invalid in MySQL 8, blocks CONVERT)
ALTER TABLE `tbl_member`
  MODIFY `modify_date` TIMESTAMP NOT NULL DEFAULT '1970-01-01 00:00:01';

-- 1. upgrade all tables to utf8mb4
ALTER TABLE `tbl_member` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_user` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_worship` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_worship_attendance` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_group` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_group_period` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_group_member` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_group_attendance` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course_member` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_course_attendance` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_hymn` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_hymn_tags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_pledge` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_pledge_member` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_issues` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_issues_reply` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_summer_activity` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tbl_summer_activity_participant` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `AuthItem` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `AuthItemChild` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `AuthAssignment` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. password column for bcrypt + Laravel auth columns
ALTER TABLE `tbl_user`
  MODIFY `password` VARCHAR(255) NOT NULL,
  ADD COLUMN `password_reset_token` VARCHAR(255) DEFAULT NULL AFTER `password`,
  ADD COLUMN `remember_token` VARCHAR(100) DEFAULT NULL AFTER `password_reset_token`,
  ADD COLUMN `last_login_at` TIMESTAMP NULL DEFAULT NULL AFTER `remember_token`;

-- 3. index for the big attendance table
ALTER TABLE `tbl_worship_attendance`
  ADD INDEX `idx_attendance_date` (`attendance_date`);

SET FOREIGN_KEY_CHECKS = 1;

-- 5. verify
SELECT TABLE_NAME, TABLE_ROWS, ENGINE, TABLE_COLLATION
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'churchsys'
ORDER BY TABLE_ROWS DESC;
