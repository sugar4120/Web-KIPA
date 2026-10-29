<?php
/**
 * Functions and definitions for KIPA Theme
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function kipa_theme_setup() {
    // Add default posts and comments RSS feed links to head.
    add_theme_support('automatic-feed-links');

    // Let WordPress manage the document title.
    add_theme_support('title-tag');

    // Enable support for Post Thumbnails on posts and pages.
    add_theme_support('post-thumbnails');

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Menu Chính (Header)', 'kipa'),
        'footer'  => __('Menu Chân Trang (Footer)', 'kipa'),
    ));

    // Switch default core markup for search form, comment form, etc. to HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ));

    // Custom logo support
    add_theme_support('custom-logo', array(
        'height'      => 80,
        'width'       => 250,
        'flex-height' => true,
        'flex-width'  => true,
    ));
}
add_action('after_setup_theme', 'kipa_theme_setup');

function kipa_enqueue_scripts() {
    // Google Fonts: Inter & Outfit
    wp_enqueue_style(
        'kipa-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap',
        array(),
        null
    );

    // FontAwesome Icons
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
        array(),
        '6.4.0'
    );

    // Theme main stylesheet
    wp_enqueue_style('kipa-style', get_stylesheet_uri(), array(), '1.0.0');

    // Custom layout & component styles
    wp_enqueue_style(
        'kipa-main-css',
        get_template_directory_uri() . '/assets/css/main.css',
        array('kipa-style'),
        '1.0.0'
    );

    // Theme JS
    wp_enqueue_script(
        'kipa-main-js',
        get_template_directory_uri() . '/assets/js/main.js',
        array(),
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'kipa_enqueue_scripts');
