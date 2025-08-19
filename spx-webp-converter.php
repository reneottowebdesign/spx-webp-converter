<?php

/**
 * Plugin Name: SPX - Webp Converter
 * Description: Konvertiert JPEG/PNG-Uploads automatisch in WebP und erlaubt WebP-Uploads.
 * Version: 1.1.1
 * Author: René Otto
 * License: GPLv2 or later
 * Text Domain: spx-webp-converter
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path constant.
if (!defined('SPX_WEBP_CONVERTER_PATH')) {
    define('SPX_WEBP_CONVERTER_PATH', plugin_dir_path(__FILE__));
}

// Load textdomain.
add_action('plugins_loaded', function () {
    load_plugin_textdomain('spx-webp-converter', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Require split include files.
require_once SPX_WEBP_CONVERTER_PATH . 'includes/functions-helpers.php';
require_once SPX_WEBP_CONVERTER_PATH . 'includes/class-spx-webp-converter-admin.php';
require_once SPX_WEBP_CONVERTER_PATH . 'includes/class-spx-webp-converter-converter.php';

// Initialize admin (only in backend).
if (is_admin()) {
    SPX_WebP_Converter_Admin::init();
}

/**
 * Allow WebP uploads in the WordPress media library.
 *
 * @param array $mimes Allowed mime types.
 * @return array Modified mime types.
 */
function spx_webp_converter_allow_webp_uploads($mimes)
{
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter('upload_mimes', 'spx_webp_converter_allow_webp_uploads');

/**
 * Convert uploaded JPEG/PNG images to WebP and replace original.
 *
 * @param array $upload Upload data.
 * @return array Modified upload data.
 */
// Register conversion filter via class wrapper.
add_filter('wp_handle_upload', ['SPX_WebP_Converter_Converter', 'convert_upload']);
