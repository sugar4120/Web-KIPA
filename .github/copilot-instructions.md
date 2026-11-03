# KIPA VIỆT HÀN - COPILOT CONTEXT & INSTRUCTIONS

## 1. Project Overview
- **Brand**: Công ty Cổ phần Hợp tác Quốc tế KIPA Việt Hàn (KIPA)
- **Domain**: https://kipaviethan.edu.vn
- **GitHub Repository**: https://github.com/sugar4120/Web-KIPA (Branch: `master`)
- **Technology Stack**:
  - Jamstack / Static HTML5, Tailwind CSS (via CDN), FontAwesome 6, Vanilla JavaScript
  - Shared template system: `header.html`, `footer.html` compiled via `build.py`
  - Hosting: LiteSpeed Web Server, cPanel Hosting (Nhân Hòa, IP: `103.28.36.122`)
  - Server configs: `.htaccess` (Clean URLs, Force HTTPS, Caching, 404 handler), `.cpanel.yml`

## 2. Brand Identity & Constants
- **Brand Colors**:
  - Brand Blue: `#0274BD`
  - Brand Red: `#EC1E1B`
  - Brand Navy: `#081D36`
  - Brand Yellow: `#FFF017`
  - Surface Background: `#F8FAFC`
- **Contact Info**:
  - Hotline: `036 851 3865` | `0889 2222 55`
  - Email: `duhocquoctekipaviethan@gmail.com`
  - Headquarters: 135 Bạch Năng Thi, Phường Tân Hưng, TP. Hải Phòng
  - Representative Offices: Seoul (South Korea) & Sydney (Australia)

## 3. Architecture & Build Process
- **Shared Components**:
  - `header.html`: Global navigation, sticky desktop menu, mobile sliding drawer, contact buttons, logo (`/Logo.jpg`).
  - `footer.html`: Global footer, social links, contact information, navigation links, copyright.
- **Build Workflow**:
  - Run `python3 build.py` to compile all 16 pages. It injects `header.html` and `footer.html`, generates root HTML files and clean URL directories (e.g. `du-hoc-han-quoc.html` and `du-hoc-han-quoc/index.html`).
- **Local Development**:
  - `python3 dev_server.py` runs local server on `http://localhost:3000`.
- **Deployment**:
  - `python3 deploy_ftp.py` uploads compiled files directly to cPanel `public_html/`.

## 4. Completed Pages (16/16 - 100% Complete)
1. `/` (`index.html`): Homepage with Hero, stats, programs, news, partner institutions.
2. `/du-hoc-han-quoc/` (`du-hoc-han-quoc.html`): South Korea Study Abroad (6 study systems, advisor widget, cost estimator, 6-step roadmap, top universities, FAQ).
3. `/du-hoc-uc/` (`du-hoc-uc.html`): Australia Study Abroad (6 study systems, interactive advisor, cost estimator, 6-step pathway, leading universities, 5-stage companion flow, accordion FAQ).
4. `/du-hoc/` (`du-hoc.html`): General study abroad hub.
5. `/gioi-thieu/` (`gioi-thieu.html`): Company introduction, vision, mission, core values.
6. `/doi-ngu/` (`doi-ngu.html`): Leadership team and teaching faculty.
7. `/tieng-han/` (`tieng-han.html`): Korean language training courses (TOPIK 1 - 6).
8. `/xuat-khau-lao-dong/` (`xuat-khau-lao-dong.html`): Labor export programs (E-7, E-9, D-4).
9. `/co-so/` (`co-so.html`): Facilities, campuses, and learning environment.
10. `/hop-tac-quoc-te/` (`hop-tac-quoc-te.html`): International university partners and MOU signings.
11. `/lien-he/` (`lien-he.html`): Contact details, consultation form, interactive Google Map.
12. `/dang-ky-tu-van/` (`dang-ky-tu-van.html`): Dedicated lead generation & consultation signup page.
13. `/faq/` (`faq.html`): Frequently asked questions & answers.
14. `/chinh-sach-bao-mat/` (`chinh-sach-bao-mat.html`): Privacy policy and user rights.
15. `/cam-on/` (`cam-on.html`): Thank-you conversion page after form submission.
16. `/404.html`: Custom 404 Not Found error page.

## 5. Live Server & Database Status
- **Live Status**: Successfully deployed and live at `https://kipaviethan.edu.vn/`.
- **Legacy WordPress**: Preserved on host (`index.php` renamed to `index_wp_backup.php`, all `wp-content` preserved).
- **Database (MySQL)**:
  - Configure connection details privately on the hosting server, outside the public web root.
  - Never put database credentials, tokens, or deployment secrets in source code or project instructions.
  - Rotate any credentials previously committed; removing them from this file does not remove Git history.

## 6. Pending Tasks & Roadmap for Next Steps
1. **Form Processing Backend**:
   - The consultation forms currently have frontend validation only.
   - Task: Create a backend endpoint (e.g. lightweight PHP or serverless API) to save form submissions to MySQL or dispatch webhook notifications (Telegram / Google Sheets / Email).
2. **SEO Optimization & Structured Data (JSON-LD)**:
   - Add Schema.org structured data (`Organization`, `EducationalOrganization`, `Course`, `BreadcrumbList`) following `KIPA - SEO Plan.xlsx`.
3. **CI/CD Automation via GitHub Actions**:
   - Create `.github/workflows/deploy.yml` to automatically build and deploy via FTP on every push to `master`.
4. **Blog / Articles Dynamic Engine**:
   - Build a lightweight template for single news articles / guides to expand SEO traffic.
