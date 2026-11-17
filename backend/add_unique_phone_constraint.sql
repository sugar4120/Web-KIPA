-- =====================================================================
-- KIPA VIỆT HÀN — BỔ SUNG RÀNG BUỘC DUY NHẤT CHO SỐ ĐIỆN THOẠI HỌC VIÊN
-- File: backend/add_unique_phone_constraint.sql
-- Mục đích: Mỗi số điện thoại chỉ được đăng ký duy nhất 1 tài khoản (1 nick)
-- Tránh việc 1 số điện thoại bị hệ thống nhận diện thành nhiều người khác nhau
-- =====================================================================

-- 1. Chuẩn hóa các số điện thoại rỗng về NULL (để không xung đột ràng buộc UNIQUE của MySQL)
UPDATE `kipa_users` SET `phone` = NULL WHERE `phone` = '' OR `phone` = ' ';

-- 2. Thêm chỉ mục DUY NHẤT (UNIQUE INDEX) cho cột phone trong bảng tài khoản kipa_users
-- Lưu ý: Nếu bảng đã có UNIQUE KEY thì lệnh này sẽ bỏ qua hoặc báo lỗi trùng tên chỉ mục
ALTER TABLE `kipa_users` ADD UNIQUE KEY `uq_kipa_user_phone` (`phone`);

-- 3. Đảm bảo bảng khách hàng CRM kipa_customers cũng có chỉ mục tra cứu điện thoại tối ưu
-- (Trong CRM nếu khách hàng cũ quay lại thì dùng chung hồ sơ khách hàng, không tạo người mới)
ALTER TABLE `kipa_customers` ADD INDEX `idx_kipa_customer_phone_clean` (`phone`);
