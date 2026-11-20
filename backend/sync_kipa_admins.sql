-- ==============================================================================
-- KIPA VIỆT HÀN - ĐỒNG BỘ CSDL BẢNG kipa_admins
-- Cơ sở dữ liệu: nhkip9ij_kipa_forms
-- Hướng dẫn thực hiện trong phpMyAdmin:
-- 1. Truy cập phpMyAdmin -> Chọn CSDL 'nhkip9ij_kipa_forms' ở cột bên trái
-- 2. Chọn tab 'Nhập' (Import) ở thanh menu trên cùng
-- 3. Bấm 'Choose File' (Chọn tệp) và chọn file này (sync_kipa_admins.sql)
-- 4. Bấm 'Thực hiện' (Go) ở cuối trang
-- ==============================================================================

-- Bước 1: Đảm bảo bảng kipa_admins có đầy đủ các cột quản trị & phân quyền
ALTER TABLE `kipa_admins` ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(160) NOT NULL DEFAULT '' AFTER `username`;
ALTER TABLE `kipa_admins` ADD COLUMN IF NOT EXISTS `email` VARCHAR(191) NULL DEFAULT NULL AFTER `full_name`;
ALTER TABLE `kipa_admins` ADD COLUMN IF NOT EXISTS `phone` VARCHAR(32) NULL DEFAULT NULL AFTER `email`;
ALTER TABLE `kipa_admins` ADD COLUMN IF NOT EXISTS `department` VARCHAR(64) NOT NULL DEFAULT 'Ban Giám Đốc' AFTER `phone`;
ALTER TABLE `kipa_admins` ADD COLUMN IF NOT EXISTS `role` VARCHAR(64) NOT NULL DEFAULT 'super_admin' AFTER `department`;
ALTER TABLE `kipa_admins` ADD COLUMN IF NOT EXISTS `scope` VARCHAR(255) NOT NULL DEFAULT 'Toàn hệ thống' AFTER `role`;
ALTER TABLE `kipa_admins` ADD COLUMN IF NOT EXISTS `status` VARCHAR(32) NOT NULL DEFAULT 'active' AFTER `scope`;
ALTER TABLE `kipa_admins` ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

-- Bước 2: Chuẩn hóa tài khoản Tổng quản trị KIPA (Super Admin gốc)
UPDATE `kipa_admins`
SET 
  `full_name` = 'Tổng quản trị KIPA',
  `email` = 'duhocquoctekipaviethan@gmail.com',
  `phone` = '036 851 3865',
  `department` = 'Ban Giám Đốc',
  `role` = 'super_admin',
  `scope` = 'Toàn hệ thống',
  `status` = 'active',
  `must_change_password` = 0
WHERE `id` = 1 OR `username` = 'admin';

-- Bước 3: Đồng bộ tài khoản nhân sự 'Kiên' vào bảng kipa_admins (nếu chưa có)
INSERT INTO `kipa_admins` (`username`, `full_name`, `email`, `phone`, `password_hash`, `department`, `role`, `scope`, `status`, `must_change_password`, `created_at`)
VALUES (
  'kiên', 
  'Kiên', 
  'phungthiphuong1080@gmail.com', 
  '0868082746', 
  '$2y$10$19F9KI0zgRtX1yE66VEhJuAtqqCGw4Le3p5ok2H5QXunw9y0yR4t.', 
  'Marketing', 
  'website_manager', 
  'Toàn Website', 
  'active', 
  0, 
  NOW()
)
ON DUPLICATE KEY UPDATE 
  `full_name` = VALUES(`full_name`),
  `email` = VALUES(`email`),
  `phone` = VALUES(`phone`),
  `department` = VALUES(`department`),
  `role` = VALUES(`role`),
  `scope` = VALUES(`scope`),
  `status` = VALUES(`status`);
