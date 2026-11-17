# KIPA VIỆT HÀN — THÔNG TIN VÀ QUY TRÌNH SAO LƯU DATABASE (DATABASE BACKUP INFO)

> **Tài liệu quản trị an toàn dữ liệu:** Chiến lược sao lưu, lưu trữ, kiểm tra tính toàn vẹn và khôi phục sự cố cho Hệ thống Database KIPA  
> **Áp dụng cho:** Website, KIPA CRM và KIPA Student Portal  

---

## I. THÔNG TIN BẢN SAO LƯU TRƯỚC TRIỂN KHAI

| Thông số | Giá trị chi tiết | Ghi chú kỹ thuật |
|---|---|---|
| **Thời điểm sao lưu mục tiêu** | Ngay trước khi chạy file `backend/crm_portal_schema.sql` | Bắt buộc thực hiện theo quy trình SOP |
| **Quy ước đặt tên file** | `kipa_db_backup_pre_crm_YYYYMMDD_HHMM.sql.gz` | Có ngày giờ và hậu tố nén gzip |
| **Phương thức sao lưu chính** | phpMyAdmin Export (Custom, Structure + Data) | Thao tác an toàn trực tiếp trên giao diện cPanel |
| **Phương thức sao lưu CLI** | `mysqldump --single-transaction --quick` | Chống lock bảng, không làm gián đoạn người dùng web |
| **Vị trí lưu trữ máy chủ** | `/home/<cpanel-user>/kipa-private/backups/` | Nằm ngoài webroot, không thể bị tải lén qua Internet |
| **Vị trí lưu trữ ngoại tuyến** | Máy tính quản trị viên KIPA & Ổ cứng bảo mật | Tuân thủ chính sách bảo vệ dữ liệu cá nhân (Nghị định 13) |
| **Dung lượng ước tính** | ~1 - 5 MB (ở thời điểm hiện tại) | Tăng dần khi CRM nạp dữ liệu khách hàng |

---

## II. QUY TRÌNH KIỂM TRA TÍNH TOÀN VẸN CỦA BẢN SAO LƯU (BACKUP VERIFICATION)

Một bản sao lưu chỉ được coi là "hợp lệ" khi vượt qua 4 bước kiểm tra sau:

### 1. Kiểm tra kích thước và phần đuôi file (Tail Verification)
- Kích thước file `.sql` phải lớn hơn 0 bytes.
- Mở xem 20 dòng cuối cùng của file dump. Bắt buộc phải có dòng chữ:
  ```text
  -- Dump completed on 2026-11-XX XX:XX:XX
  ```
  *(Nếu không có dòng này, quá trình dump bị gián đoạn giữa chừng do timeout hoặc hết dung lượng đĩa).*

### 2. Kiểm tra tính toàn vẹn mã băm (SHA-256 Checksum)
Sau khi tải file backup về máy tính quản trị viên, tạo mã băm SHA-256 để kiểm chứng:
- Trên Windows PowerShell:
  ```powershell
  Get-FileHash -Algorithm SHA256 kipa_db_backup_pre_crm_*.sql
  ```
- Lưu lại chuỗi Hash này vào sổ nhật ký kỹ thuật để đối chiếu khi cần phục hồi.

### 3. Kiểm tra khả năng đọc và cú pháp SQL (Syntax Dry-Run)
- Đảm bảo trong file có đầy đủ các câu lệnh `CREATE TABLE` của 5 bảng cũ (`kipa_leads`, `kipa_rate_limits`, `kipa_otp_codes`, `kipa_users`, `kipa_admins`).
- Đảm bảo các khối dữ liệu `INSERT INTO` chứa đầy đủ các bản ghi hiện tại.

---

## III. CHÍNH SÁCH SAO LƯU ĐỊNH KỲ TỰ ĐỘNG TRÊN CPANEL (AUTOMATED BACKUP SLA)

Sau khi hệ thống CRM và Student Portal đi vào vận hành với dữ liệu thật của khách hàng, KIPA thiết lập chính sách sao lưu định kỳ:

| Tần suất sao lưu | Thời điểm chạy | Phương thức | Thời gian lưu trữ (Retention) | Mục tiêu |
|---|---|---|---|---|
| **Hằng ngày (Daily)** | 02:00 sáng | Cronjob mysqldump nén gz | Lưu giữ 30 ngày gần nhất | Phục hồi sự cố hằng ngày |
| **Hằng tuần (Weekly)**| Chủ nhật 03:00 | cPanel Full Backup | Lưu giữ 12 tuần | Đối chiếu dữ liệu tháng |
| **Hằng tháng (Monthly)**| Ngày 01 hàng tháng | Export ngoại tuyến | Lưu giữ 3 năm | Lưu trữ hồ sơ pháp lý du học |

### Lệnh cấu hình Cronjob sao lưu tự động trên cPanel:
```sh
0 2 * * * mysqldump -u <db_user> -p'<db_password>' <db_name> 2>/dev/null | gzip > /home/<user>/kipa-private/backups/kipa_db_$(date +\%Y\%m\%d).sql.gz && find /home/<user>/kipa-private/backups/ -name "kipa_db_*.sql.gz" -mtime +30 -delete
```
*(Lệnh trên tự động nén file sao lưu mỗi đêm lúc 2 giờ sáng và tự động xóa các file sao lưu cũ hơn 30 ngày để tiết kiệm dung lượng hosting).*

---

## IV. CHỈ SỐ MỤC TIÊU PHỤC HỒI DỮ LIỆU (DISASTER RECOVERY TARGETS)

- **RPO (Recovery Point Objective - Điểm phục hồi tối đa):** $\le 24\text{ giờ}$  
  Trong trường hợp xấu nhất máy chủ hosting bị lỗi phần cứng, dữ liệu khách hàng và học viên bị mất tối đa không quá 24 giờ kể từ bản backup 02:00 sáng gần nhất.
- **RTO (Recovery Time Objective - Thời gian phục hồi hệ thống):** $\le 15\text{ phút}$  
  Thời gian cần thiết để kỹ sư import bản backup phục hồi lại toàn bộ website, CRM và Portal là dưới 15 phút.
