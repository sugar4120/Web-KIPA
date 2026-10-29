<?php
/**
 * The template for displaying the footer
 */
?>
<!-- Footer -->
<footer class="kipa-footer">
    <div class="container">
        <div class="footer-grid">
            <!-- Col 1: About -->
            <div class="footer-col">
                <div class="site-logo" style="margin-bottom: 18px;">
                    <div class="logo-mark">K</div>
                    <div class="logo-text">
                        <span class="brand-name" style="color: #fff;">KIPA VIỆT HÀN</span>
                        <span class="brand-sub" style="color: #adb5bd;">Định hướng tương lai</span>
                    </div>
                </div>
                <p style="font-size: 0.9rem; line-height: 1.7; color: #ced4da;">
                    KIPA Việt Hàn là tổ chức uy tín hàng đầu chuyên cung cấp giải pháp du học Hàn Quốc, Úc, Châu Âu và các chương trình việc làm, kỹ sư quốc tế với lộ trình minh bạch, chi phí tối ưu.
                </p>
            </div>

            <!-- Col 2: Programs -->
            <div class="footer-col">
                <h4>Chương Trình Nổi Bật</h4>
                <ul class="footer-links">
                    <li><a href="#du-hoc-tieng-han"><i class="fa-solid fa-angle-right"></i> Du học tiếng Hàn (Visa D4-1)</a></li>
                    <li><a href="#du-hoc-chuyen-nganh"><i class="fa-solid fa-angle-right"></i> Du học chuyên ngành (Visa D2)</a></li>
                    <li><a href="#du-hoc-nghe"><i class="fa-solid fa-angle-right"></i> Du học nghề chuyển đổi</a></li>
                    <li><a href="#du-hoc-uc"><i class="fa-solid fa-angle-right"></i> Du học & định cư Úc</a></li>
                    <li><a href="#dao-tao-tieng"><i class="fa-solid fa-angle-right"></i> Đào tạo Topik cấp tốc</a></li>
                </ul>
            </div>

            <!-- Col 3: Quick links -->
            <div class="footer-col">
                <h4>Liên Kết Nhanh</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo esc_url(home_url('/')); ?>"><i class="fa-solid fa-angle-right"></i> Trang chủ</a></li>
                    <li><a href="#ve-chung-toi"><i class="fa-solid fa-angle-right"></i> Về KIPA Việt Hàn</a></li>
                    <li><a href="#chinh-sach-hoc-bong"><i class="fa-solid fa-angle-right"></i> Chính sách học bổng</a></li>
                    <li><a href="#cau-chuyen-thanh-cong"><i class="fa-solid fa-angle-right"></i> Học viên tiêu biểu</a></li>
                    <li><a href="#lien-he"><i class="fa-solid fa-angle-right"></i> Liên hệ tư vấn</a></li>
                </ul>
            </div>

            <!-- Col 4: Contact -->
            <div class="footer-col">
                <h4>Trụ Sở Chính</h4>
                <ul class="footer-contact">
                    <li><i class="fa-solid fa-location-dot" style="color: var(--accent-color); margin-top: 4px;"></i> Hà Nội & TP. Hồ Chí Minh, Việt Nam</li>
                    <li><i class="fa-solid fa-phone" style="color: var(--accent-color);"></i> Hotline: 0981 234 567</li>
                    <li><i class="fa-solid fa-envelope" style="color: var(--accent-color);"></i> tuyensinh@kipaviethan.edu.vn</li>
                    <li><i class="fa-solid fa-clock" style="color: var(--accent-color);"></i> T2 - T7: 8:00 - 18:00</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <strong>KIPA Việt Hàn</strong>. All Rights Reserved. Xây dựng và vận hành trên nền tảng WordPress.</p>
        </div>
    </div>
</footer>

<!-- Floating Action Buttons -->
<div class="floating-actions">
    <a href="tel:0981234567" class="floating-btn hotline" title="Gọi Hotline" aria-label="Gọi hotline">
        <i class="fa-solid fa-phone"></i>
    </a>
    <a href="https://zalo.me" target="_blank" rel="noopener" class="floating-btn zalo" title="Chat Zalo" aria-label="Chat Zalo">
        <i class="fa-solid fa-comment-dots"></i>
    </a>
</div>

<?php wp_footer(); ?>
</body>
</html>
