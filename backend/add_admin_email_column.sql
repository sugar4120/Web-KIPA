-- ========================================================
-- KIPA VIỆT HÀN - BỔ SUNG CỘT EMAIL VÀO BẢNG KIPA_ADMINS
-- Hỗ trợ xác thực OTP đặt lại mật khẩu Quản trị viên
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Bổ sung cột email (nếu chưa tồn tại)
ALTER TABLE `kipa_admins` 
ADD COLUMN IF NOT EXISTS `email` VARCHAR(191) NULL DEFAULT NULL AFTER `username`;

-- Thêm chỉ mục UNIQUE cho email quản trị (nếu chưa có)
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

-- Cập nhật email mặc định cho admin nếu chưa có (ví dụ: email quản trị KIPA)
UPDATE `kipa_admins` 
SET `email` = 'duhocquoctekipaviethan@gmail.com' 
WHERE `username` = 'admin' AND (`email` IS NULL OR `email` = '');

SET FOREIGN_KEY_CHECKS = 1;
