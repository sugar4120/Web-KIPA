# Cơ sở dữ liệu tập trung KIPA

Thiết kế theo sheet **Database - Cơ sở dữ liệu KIPA** trong bản SEO Plan do
người dùng cung cấp. Các số `source_row` trong [data dictionary](../api/content-model.json)
là dòng gốc trong Excel. Không nhập sheet tài khoản hoặc dữ liệu minh họa thành
dữ liệu kinh doanh thật.

## Kiến trúc hybrid

```text
Form công khai ──> submit.php ──> kipa_leads (tương thích hệ thống cũ)
                           └──> bản ghi nghiệp vụ G + lịch sử gửi form

Tài khoản quản trị hiện có ──> cms.php ──> MySQL, lưu nháp / sửa nội dung
                                        │
                                  Bấm Xuất bản
                                        │
                                HTML tĩnh + sitemap
                                /noi-dung/ và /tin-tuc/
```

Giữ nguyên 16 trang thiết kế sẵn và WordPress dự phòng. CMS **chưa thay thế nội
dung hardcode trong 16 trang này**. Dữ liệu mới có giao diện công khai riêng,
chạy song song để có thể chuyển từng phần mà không làm mất website hiện có.

## 10 nhóm, 85 bảng nội dung

Tất cả tên bảng có tiền tố `kipa_`. Ba bảng cũ `kipa_leads`,
`kipa_admins`, `kipa_rate_limits` được giữ nguyên. `kipa_content_state` ghi nhận
phiên bản và fingerprint của mô hình đã migration.

| Nhóm | Số bảng | Phạm vi |
|---|---:|---|
| A | 9 | programs, study_abroad_programs, study_program_details, work_programs, partnership_programs, program_media, work_industries, work_job_positions, work_program_costs |
| B | 9 | countries, schools, study_fields, school_programs, partners, partner_types, school_media, partner_schools, partner_media |
| C | 9 | program_requirements, program_fees, program_intakes, intake_milestones, program_documents, document_categories, program_process_steps, program_fee_groups, program_information_sources |
| D | 11 | courses, course_levels, course_roadmaps, course_roadmap_steps, teachers, course_teachers, course_intakes, course_fees, course_faqs, course_schedules, course_media |
| E | 9 | locations, location_addresses, location_contacts, location_coordinates, location_media, location_services, location_hours, location_service_assignments, location_facilities |
| F | 12 | posts, post_categories, post_tags, post_category_assignments, post_tag_assignments, authors, events, media_galleries, media_gallery_items, pages, post_revisions, page_revisions |
| G | 10 | consultation_requests, consultation_interests, consultation_request_interests, contact_messages, course_registrations, event_registrations, lead_sources, form_submissions, consultation_activities, registration_statuses |
| H | 4 | faqs, faq_categories, program_faqs, policies |
| I | 4 | testimonials, success_stories, videos, activities |
| J | 8 | seo_metadata, redirects, menus, menu_items, banners, site_settings, media_files, media_usages |

### Các quyết định chuẩn hóa

- Dùng `BIGINT UNSIGNED` làm khóa chính/ngoại; InnoDB, UTF-8 `utf8mb4`.
- `schools.country_id` thay bảng trung gian `school_countries`, đúng phương án
  đơn giản được Excel khuyến nghị. Châu Âu không phải một quốc gia.
- Hợp nhất `language_courses` vào `courses`; giữ thêm điều kiện đầu vào, đầu ra,
  giáo trình và sĩ số, liên kết về chương trình gốc bằng `program_id`.
- Chỉ một bảng `videos`, dùng chung cho F và I; bổ sung trường phỏng vấn và xác
  nhận đồng ý công khai.
- SEO chỉ lưu ở `seo_metadata`, unique `(entity_type, entity_id)`. Không lưu thêm
  `seo_title`/`seo_description` trong mỗi bảng nội dung. CMS chấp nhận `post`,
  `page`, `program`… và chuẩn hóa thành tên bảng số nhiều.
- `media_files` là danh mục file gốc, `media_usages` là liên kết sử dụng chung.
  Các trường URL ảnh của các nhóm vẫn được giữ để tương thích thiết kế Excel;
  chưa bắt buộc chuyển mọi ảnh cũ thành khóa ngoại.
- Giữ danh mục `document_categories` và `program_fee_groups` để phát triển tiếp;
  các trường phân loại văn bản gốc chưa tự động chuyển thành FK nếu Excel chưa
  xác định một danh mục bắt buộc.
- FK thực cho tham chiếu đã xác định; tham chiếu đa hình
  `entity_type/entity_id`, `form_type/form_record_id` được kiểm tra tại backend.
  `student_id` không có FK vì tài liệu chưa định nghĩa bảng học viên.
- Nhóm điều kiện/chi phí/kỳ tuyển/hồ sơ/quy trình chọn đúng một chủ thể du học,
  việc làm hoặc khóa học; kiểm tra ở backend và `CHECK` trong database hỗ trợ.
- Slug duy nhất trong từng bảng; unique trên mã yêu cầu/đăng ký, khóa cấu hình
  và các cặp liên kết nhiều-nhiều chính. Xóa dữ liệu đang được sử dụng bị chặn.
- `legacy_lead_id` unique liên kết bản ghi G với `kipa_leads`. Xóa đăng ký gốc
  cascade sang bản ghi nghiệp vụ và log tương ứng. Lịch sử tư vấn cascade khi
  xóa yêu cầu; revision bài viết/trang cascade khi xác nhận xóa nội dung cha.

### Những khác biệt cần thiết so với đề xuất Excel

- Email ở các form không bị bắt buộc thêm nếu trước đây không bắt buộc.
- Điện thoại được giữ tối đa 32 byte như API đang hoạt động, thay vì 30.
- `course_registrations.course_id` tạm cho phép NULL: các form cũ có nhu cầu học
  nhưng chưa chọn ID khóa học trong database. Giữ nguyên giá trị họ đã gửi,
  không tự gán một khóa học không đúng; quản trị viên có thể bổ sung liên kết.
- `redirects.source_url` giới hạn 500 ký tự để unique hoạt động đầy đủ với
  `utf8mb4`; không dùng unique prefix gây trùng giả.
- Không lưu IP thô hay user-agent từ các form hiện tại. Chống spam vẫn dùng
  HMAC của IP với thời hạn cũ; các cột tương ứng trong đề xuất được để NULL.
- Dữ liệu học viên cần `consent_confirmed = 1` trước khi xuất bản, không chỉ có
  trạng thái published.

## Migration không phá dữ liệu

Khuyến nghị dùng CLI, **không import SQL lặp lại bằng tay**:

```bash
/opt/alt/php83/usr/bin/php /home/<cpanel-user>/public_html/api/migrate.php --apply
```

Migration:

1. Lấy khóa chống chạy song song.
2. Tạo bản sao SQL riêng của ba bảng cũ trong `kipa-private`,
   `database-before-content-*.sql`, quyền `0600`.
3. Tạo bảng mới, index và FK; kiểm tra lại các cột cần thiết.
4. Trong transaction, khóa tập lead gốc, backfill bản ghi chưa có log, sau đó
   đánh dấu phiên bản đã sẵn sàng.
5. Chạy lại không nhân đôi dữ liệu, không đổi mật khẩu quản trị.

DDL MySQL có implicit commit: nếu một bước tạo bảng lỗi thì các bảng đã tạo
có thể còn lại, nhưng hệ thống cũ vẫn giữ dữ liệu và CMS chưa được kích hoạt.
Sửa nguyên nhân và chạy lại. Không dùng `DROP`, không tự rollback bằng cách
xóa bảng. Nếu fingerprint thay đổi sau khi đã cài đặt, migration dừng và yêu
cầu một bản migration nâng cấp riêng thay vì giả vờ đã cập nhật schema.

[content-schema.sql](./content-schema.sql) là bản thiết kế SQL có FK, sinh từ
`php api/migrate.php --print`. Nó cần ba bảng gốc trong
[schema.sql](./schema.sql) và không thay thế bước backfill/kích hoạt của CLI.

## Quản trị và xuất bản

- Đăng nhập tài khoản hiện có tại `/api/admin.php`, chọn **Quản trị dữ liệu A–J**,
  hoặc mở `/api/cms.php`.
- Tài khoản quản trị đầu tiên được khởi tạo một lần bằng `setup_token` trong cấu
  hình riêng trên hosting. Sau khi khởi tạo, dùng tài khoản được cấu hình sẵn
  để đăng nhập với toàn quyền quản trị; không có chức năng tạo thêm quản trị
  viên trong dashboard. Mật khẩu được lưu dưới dạng băm, không đặt sẵn trong
  mã nguồn. Đổi mật khẩu yêu cầu 12–72 byte.
- Tài khoản được cấp bằng CLI bên dưới bắt buộc đổi mật khẩu trước khi xem
  đăng ký, sửa dữ liệu hoặc xuất bản. Backend kiểm tra cờ trong database ở cả
  trang quản trị và CMS, không chỉ ẩn giao diện.

### Cấp tài khoản admin có mật khẩu ban đầu

Sao lưu database trước. Chạy công cụ từ bản mã nguồn riêng, không đưa thư mục
`backend` vào webroot. Trên hosting đã có bảng `kipa_admins`, chạy migration
**trước khi triển khai PHP mới**, kể cả khi không cần tạo tài khoản:

```bash
php backend/admin_account.php --migrate-only /home/<cpanel-user>/public_html
```

Sau đó tạo tài khoản `admin`, nhập mật khẩu ban đầu qua stdin từ một shell
Bash tin cậy; không ghi mật khẩu vào lệnh, file SQL hay Git:

```bash
read -r -s -p 'Initial password: ' initial_password
printf '\n'
printf '%s\n' "$initial_password" | php backend/admin_account.php --create /home/<cpanel-user>/public_html
unset initial_password
```

CLI chỉ lưu bản băm và đặt `must_change_password = 1`, không in mật khẩu.
Nếu `admin` đã tồn tại, công cụ dừng, không đặt lại mật khẩu hay ghi đè tài khoản.
Tài khoản khác vẫn được giữ nguyên. Mật khẩu ban đầu yếu chỉ nên dùng trong môi
trường được giới hạn truy cập; người biết nó vẫn có thể đăng nhập trước chủ sở
hữu. Đổi ngay sau khi cấp, không để tài khoản với mật khẩu mặc định trên Internet.

### Chức năng CMS

- Các bảng có danh sách, tìm kiếm, phân trang 50 bản ghi, thêm/sửa/xóa với xác
  nhận. Datalist FK gợi ý 300 bản ghi gần nhất; vẫn nhập được ID hợp lệ khác.
- Kiểm tra đầu vào, FK, lựa chọn, ngày giờ, JSON, khoảng số; kiểm tra vòng lặp
  phân cấp và redirect. Form có CSRF, kiểm tra Origin và chống ghi đè một bản
  sửa mới hơn.
- Revision bài viết/trang và lịch sử đổi trạng thái tư vấn được ghi khi sửa
  từ CMS. Log gửi form/revision chỉ đọc trong giao diện.
- Dữ liệu đăng ký nhập qua website được ghi **cùng transaction** ở bảng cũ và
  bảng nghiệp vụ mới. Trang quản trị cũ là bản gửi gốc, không phải nguồn trạng
  thái xử lý mới; quản lý trạng thái trong nhóm G.
- Dữ liệu khách hàng và log được giữ theo thời hạn 12 tháng hiện tại, bao gồm
  bản ghi nghiệp vụ và lịch sử liên quan. Cron cleanup hiện có vẫn được dùng.

### Nội dung công khai hiện đã nối

Xuất bản tạo các trang tĩnh cho chương trình, hệ học du học, việc làm, hợp tác,
quốc gia, trường, đối tác, khóa học, giáo viên, cơ sở, bài viết, trang nội dung,
sự kiện, FAQ, chính sách, cảm nhận/câu chuyện, video và hoạt động. Chỉ trạng thái
`published` được hiển thị (giáo viên dùng `active` theo Excel).
Đối tác còn cần quan hệ `active`; nội dung học viên còn cần xác nhận đồng ý.

Thông tin cơ sở có địa chỉ/liên hệ/dịch vụ; khóa học có lộ trình/FAQ/lịch mở
lớp/học phí; trường có chương trình; hệ học/việc làm có điều kiện, chi phí,
checklist hồ sơ và các bước triển khai. Bản ghi con phải có trạng thái công
khai phù hợp. Văn bản thuần được escape, không chạy HTML do người dùng nhập.

- Bài viết: `/tin-tuc/{slug}/`.
- Nội dung khác: `/noi-dung/{ten-bang-dung-dau-gach-ngang}/{slug-hoac-id}/`.
- SEO, canonical, robots, JSON-LD áp dụng theo `seo_metadata`.
- Sitemap nội dung: `/noi-dung/sitemap.xml`; sitemap của 16 trang cũ vẫn nguyên.
- Menu có trạng thái active/published và các mục tương ứng hiển thị trên trang
  CMS. Hiện hiển thị dạng liên kết phẳng, chưa có trình kéo-thả menu.
- Cấu hình công khai hỗ trợ `site_title`, `hotline`, `email`. Không lưu khóa API,
  token hay mật khẩu trong `site_settings`.
- Banner toàn cục dùng `position = cms_header`, `is_active = 1`, không gán
  entity; kiểm tra khoảng thời gian tại **thời điểm xuất bản**.
- Redirect có source là đường dẫn nội bộ ASCII, target nội bộ/HTTPS, mã
  301/302/307/308. Chặn vòng lặp, gộp chuỗi về đích cuối và không chuyển hướng
  endpoint quản trị/WordPress bị bảo vệ. Quy tắc sinh trong vùng đánh dấu của
  `.htaccess`; triển khai Git giữ lại vùng này.

Nháp không tự xuất hiện. Đổi trạng thái/gỡ nội dung cần **Xuất bản** lại.
Các mốc hẹn giờ cũng cần xuất bản lại; chưa có cron tự xuất bản bài hẹn giờ.
Publisher tạo bản mới ngoài webroot, kiểm tra quyền sở hữu thư mục đích, đổi
thư mục với bản dự phòng và khôi phục nếu kích hoạt lỗi. Bản cũ được gỡ để
không còn truy cập được URL của nội dung đã rút xuống. Không đụng thư mục
không có dấu sở hữu của CMS.

Git/FTPS không xóa file server và không chứa các thư mục xuất bản trên; bài
viết đã xuất bản không bị một lần deploy code ghi đè. Cần sao lưu **cả database
và nội dung/media trên hosting**; Git không phải bản sao lưu các chỉnh sửa CMS.

## Phần chưa chuyển đổi

- Nội dung hardcode, menu, header/footer và Schema hiện tại của 16 trang cũ
  chưa tự đổi theo CMS. Chuyển từng trang cần mapping đúng từng thành phần
  giao diện, không thay cả trang bằng một template chung.
- Chưa có trình rich text, kéo-thả, upload file hay xử lý ảnh. `media_files`
  hiện quản lý metadata/URL của file đã tải lên hosting; cờ `is_public` trong
  metadata không phải cơ chế bảo vệ một URL bên ngoài.
- Chưa có form công khai đăng ký sự kiện riêng hoặc tự suy luận khóa học từ
  các lựa chọn cũ. CTA nội dung hiện dùng form tư vấn đã hoạt động.
- Chưa có phân quyền nhiều vai trò, workflow duyệt bài nhiều người, xuất CRM,
  dịch vụ email/Telegram hoặc hồ sơ học viên nội bộ.
- Không seed ví dụ trong Excel thành trường đối tác, học phí, lịch tuyển,
  đánh giá hoặc học viên thật.

## Kiểm thử / tái sinh mô hình

Không cần cài thư viện đọc Excel:

```bash
python3 backend/import_plan.py "/duong-dan/KIPA Việt Hàn - SEO Plan.xlsx"
php api/migrate.php --print > backend/content-schema.sql
python3 -m unittest discover -s tests -v
```

Sau khi đã triển khai version 1, không thay mô hình rồi chạy lại `--apply` để
nâng cấp: cần migration version kế tiếp có kiểm thử dữ liệu.

Kiểm thử MySQL dùng database trống **chỉ dành cho test**:

```bash
KIPA_TEST_DSN='mysql:host=127.0.0.1;port=3306;dbname=kipa_content_test;charset=utf8mb4' \
  php tests/test_content.php
```

Bộ test yêu cầu MySQL test với root không mật khẩu, tạo user test riêng; không
dùng thông tin hosting. CI tự khởi tạo service MySQL 8 dùng một lần, kiểm tra
85 bảng, chạy migration hai lần, backfill, mật khẩu cũ, 6 form HTTP lưu thực,
CRUD, CSRF, Origin, revision chống sửa đè, SEO/JSON-LD, không xuất dữ liệu riêng,
gỡ trang đã ẩn và bảo vệ thư mục không thuộc CMS.
