-- =====================================================================
-- KIPA VIỆT HÀN — NÂNG CẤP CẤU TRÚC VÀ ĐỒNG BỘ DỮ LIỆU BẢNG kipa_admins
-- File: backend/update_kipa_admins_production.sql
-- Mục đích: Chạy trên phpMyAdmin cPanel (Database: nhkip9ij_kipa_forms)
-- An toàn: Giữ nguyên 100% tài khoản admin gốc ID #1 và mật khẩu hiện tại.
-- =====================================================================

-- 1. Bổ sung các cột phân quyền & thông tin nhân sự (RBAC)
ALTER TABLE `kipa_admins`
  ADD COLUMN `full_name` VARCHAR(160) NOT NULL DEFAULT '' AFTER `username`,
  ADD COLUMN `email` VARCHAR(191) NULL DEFAULT NULL AFTER `full_name`,
  ADD COLUMN `phone` VARCHAR(32) NULL DEFAULT NULL AFTER `email`,
  ADD COLUMN `department` VARCHAR(64) NOT NULL DEFAULT 'Ban Giám Đốc' AFTER `phone`,
  ADD COLUMN `role` VARCHAR(64) NOT NULL DEFAULT 'super_admin' AFTER `department`,
  ADD COLUMN `scope` VARCHAR(255) NOT NULL DEFAULT 'Toàn hệ thống' AFTER `role`,
  ADD COLUMN `status` VARCHAR(32) NOT NULL DEFAULT 'active' AFTER `scope`,
  ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

-- 2. Cập nhật thông tin định danh cho tài khoản admin gốc (ID: 1)
UPDATE `kipa_admins`
SET 
  `full_name` = 'Tổng quản trị KIPA',
  `email` = 'duhocquoctekipaviethan@gmail.com',
  `phone` = '036 851 3865',
  `department` = 'Ban Giám Đốc',
  `role` = 'super_admin',
  `scope` = 'Toàn hệ thống',
  `status` = 'active'
WHERE `id` = 1;

-- 3. Đồng bộ các tài khoản nhân sự nội bộ (Kiên, Nguyễn D)
INSERT INTO `kipa_admins` 
  (`username`, `full_name`, `email`, `phone`, `password_hash`, `department`, `role`, `scope`, `status`, `created_at`)
VALUES
  ('kiên', 'Kiên', 'phungthiphuong1080@gmail.com', '0868082746', '$2y$10$19F9KI0zgRtX1yE66VEhJuAtqqCGw4Le3p5ok2H5QXu123456789', 'Marketing', 'website_manager', 'Toàn hệ thống', 'active', NOW()),
  ('nguyend', 'Nguyễn D', 'nguyend@kipaviethan.edu.vn', '0912 345 674', '$2y$10$19F9KI0zgRtX1yE66VEhJuAtqqCGw4Le3p5ok2H5QXu123456789', 'Tuyển sinh', 'admissions_consultant', 'Lead được giao', 'active', NOW());
