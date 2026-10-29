<?php
/**
 * The header for our theme
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Topbar -->
<div class="kipa-topbar">
    <div class="container">
        <div class="topbar-info">
            <span><i class="fa-solid fa-phone"></i> Hotline: <a href="tel:0981234567"><strong>0981 234 567</strong></a></span>
            <span><i class="fa-solid fa-envelope"></i> Email: <a href="mailto:tuyensinh@kipaviethan.edu.vn">tuyensinh@kipaviethan.edu.vn</a></span>
        </div>
        <div class="topbar-right">
            <span class="topbar-badge"><i class="fa-solid fa-award"></i> Đơn vị tư vấn du học & XKLĐ uy tín</span>
        </div>
    </div>
</div>

<!-- Header -->
<header class="kipa-header">
    <div class="container">
        <div class="header-wrapper">
            <!-- Brand / Logo -->
            <a href="<?php echo esc_url(home_url('/')); ?>" class="site-logo">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <div class="logo-mark">K</div>
                    <div class="logo-text">
                        <span class="brand-name">KIPA VIỆT HÀN</span>
                        <span class="brand-sub">Du học & Xuất khẩu lao động</span>
                    </div>
                <?php endif; ?>
            </a>

            <!-- Navigation -->
            <nav class="kipa-nav" aria-label="<?php esc_attr_e('Main Navigation', 'kipa'); ?>">
                <?php
                if (has_nav_menu('primary')) {
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'menu_class'     => 'nav-menu',
                        'container'      => false,
                    ));
                } else {
                    echo '<ul class="nav-menu">';
                    echo '<li class="current-menu-item"><a href="' . esc_url(home_url('/')) . '">Trang chủ</a></li>';
                    echo '<li><a href="#du-hoc-han-quoc">Du học Hàn Quốc</a></li>';
                    echo '<li><a href="#du-hoc-uc">Du học Úc</a></li>';
                    echo '<li><a href="#xkld">Xuất khẩu lao động</a></li>';
                    echo '<li><a href="#tin-tuc">Tin tức & Học bổng</a></li>';
                    echo '<li><a href="#lien-he">Liên hệ</a></li>';
                    echo '</ul>';
                }
                ?>
                <a href="#tu-van" class="header-cta-btn"><i class="fa-solid fa-paper-plane"></i> Đăng Ký Tư Vấn</a>
            </nav>

            <!-- Mobile Hamburger Toggle -->
            <button class="mobile-toggle" aria-label="Mở menu điều hướng" aria-expanded="false">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>
</header>
