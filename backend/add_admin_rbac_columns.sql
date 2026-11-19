-- ========================================================
-- KIPA VIỆT HÀN - BỔ SUNG CÁC CỘT PHÂN QUYỀN VÀO KIPA_ADMINS
-- Hỗ trợ 8 vai trò quản trị & phân quyền RBAC
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Bổ sung các cột phân quyền nếu chưa tồn tại
ALTER TABLE `kipa_admins`
ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(160) NOT NULL DEFAULT '' AFTER `username`,
ADD COLUMN IF NOT EXISTS `email` VARCHAR(191) NULL DEFAULT NULL AFTER `full_name`,
ADD COLUMN IF NOT EXISTS `phone` VARCHAR(32) NULL DEFAULT NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `department` VARCHAR(64) NOT NULL DEFAULT 'Ban Giám Đốc' AFTER `phone`,
ADD COLUMN IF NOT EXISTS `role` VARCHAR(64) NOT NULL DEFAULT 'super_admin' AFTER `department`,
ADD COLUMN IF NOT EXISTS `scope` VARCHAR(255) NOT NULL DEFAULT 'Toàn hệ thống' AFTER `role`,
ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) NOT NULL DEFAULT 'active' AFTER `scope`;

-- Thêm chỉ mục UNIQUE cho email (nếu chưa có)
SET @index_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'kipa_admins' 
      AND INDEX_NAME = 'uq_kipa_admin_email'
);
SET @sql = IF(@index_exists = 0, 
    'ALTER TABLE `kipa_admins` ADD UNIQUE KEY `uq_kipa_admin_email` (`email`)', 
    'SELECT "Index uq_kipa_admin_email already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Cập nhật thông tin chi tiết cho tài khoản admin gốc
UPDATE `kipa_admins`
SET `full_name` = 'Tổng quản trị KIPA',
    `department` = 'Ban Giám Đốc',
    `role` = 'super_admin',
    `scope` = 'Toàn hệ thống',
    `status` = 'active',
    `email` = IF(`email` IS NULL OR `email` = '', 'duhocquoctekipaviethan@gmail.com', `email`)
WHERE `username` = 'admin';

SET FOREIGN_KEY_CHECKS = 1;
