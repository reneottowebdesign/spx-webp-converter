<?php

/**
 * Plugin Name: SPX - Webp Converter
 * Description: Konvertiert JPEG/PNG-Uploads automatisch in WebP und erlaubt WebP-Uploads.
 * Version: 1.1.0
 * Author: René Otto
 * License: GPLv2 or later
 * Text Domain: spx-webp-converter
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load plugin textdomain for translations.
 */
function spx_webp_converter_load_textdomain(): void
{
    load_plugin_textdomain('spx-webp-converter', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'spx_webp_converter_load_textdomain');

// -----------------------------------------------------------------------------
// Settings (Quality & Max Dimensions)
// -----------------------------------------------------------------------------

/**
 * Get configured WebP quality.
 *
 * @return int 0-100
 */
function spx_webp_converter_get_quality(): int
{
    $q = (int) get_option('spx_webp_converter_quality', 80);
    if ($q < 0) {
        $q = 0;
    } elseif ($q > 100) {
        $q = 100;
    }
    return $q;
}

/**
 * Get configured max width (0 means unlimited).
 *
 * @return int
 */
function spx_webp_converter_get_max_width(): int
{
    $w = (int) get_option('spx_webp_converter_max_width', 0);
    return $w < 0 ? 0 : $w;
}

/**
 * Get configured max height (0 means unlimited).
 *
 * @return int
 */
function spx_webp_converter_get_max_height(): int
{
    $h = (int) get_option('spx_webp_converter_max_height', 0);
    return $h < 0 ? 0 : $h;
}

/**
 * Register settings.
 */
function spx_webp_converter_register_settings(): void
{
    register_setting(
        'spx_webp_converter_settings',
        'spx_webp_converter_quality',
        array(
            'type' => 'integer',
            'sanitize_callback' => function ($value) {
                $value = (int) $value;
                if ($value < 0) {
                    $value = 0;
                } elseif ($value > 100) {
                    $value = 100;
                }
                return $value;
            },
            'default' => 80,
        )
    );

    foreach (array('width', 'height') as $axis) {
        register_setting(
            'spx_webp_converter_settings',
            'spx_webp_converter_max_' . $axis,
            array(
                'type' => 'integer',
                'sanitize_callback' => function ($value) {
                    $value = (int) $value;
                    return $value < 0 ? 0 : $value;
                },
                'default' => 0,
            )
        );
    }

    add_settings_section(
        'spx_webp_converter_main',
        __('WebP Conversion Settings', 'spx-webp-converter'),
        function () {
            echo '<p>' . esc_html__('Configure conversion quality and optional maximum dimensions. Leave max dimensions at 0 for no limit.', 'spx-webp-converter') . '</p>';
        },
        'spx_webp_converter'
    );

    add_settings_field(
        'spx_webp_converter_quality',
        __('Quality (0-100)', 'spx-webp-converter'),
        function () {
            printf(
                '<input type="number" min="0" max="100" name="spx_webp_converter_quality" value="%d" class="small-text" />',
                esc_attr(spx_webp_converter_get_quality())
            );
        },
        'spx_webp_converter',
        'spx_webp_converter_main'
    );

    add_settings_field(
        'spx_webp_converter_max_width',
        __('Max Width (px, 0 = unlimited)', 'spx-webp-converter'),
        function () {
            printf(
                '<input type="number" min="0" name="spx_webp_converter_max_width" value="%d" class="small-text" />',
                esc_attr(spx_webp_converter_get_max_width())
            );
        },
        'spx_webp_converter',
        'spx_webp_converter_main'
    );

    add_settings_field(
        'spx_webp_converter_max_height',
        __('Max Height (px, 0 = unlimited)', 'spx-webp-converter'),
        function () {
            printf(
                '<input type="number" min="0" name="spx_webp_converter_max_height" value="%d" class="small-text" />',
                esc_attr(spx_webp_converter_get_max_height())
            );
        },
        'spx_webp_converter',
        'spx_webp_converter_main'
    );
}
add_action('admin_init', 'spx_webp_converter_register_settings');

/**
 * Add settings page.
 */
function spx_webp_converter_add_settings_page(): void
{
    add_options_page(
        __('SPX WebP Converter', 'spx-webp-converter'),
        __('SPX WebP Converter', 'spx-webp-converter'),
        'manage_options',
        'spx_webp_converter',
        'spx_webp_converter_render_settings_page'
    );
}
add_action('admin_menu', 'spx_webp_converter_add_settings_page');

/**
 * Render settings page.
 */
function spx_webp_converter_render_settings_page(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'spx-webp-converter'));
    }
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('SPX WebP Converter Settings', 'spx-webp-converter') . '</h1>';
    echo '<form action="options.php" method="post">';
    settings_fields('spx_webp_converter_settings');
    do_settings_sections('spx_webp_converter');
    submit_button();
    echo '</form>';
    echo '</div>';
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
function spx_webp_converter_convert_image_to_webp_on_upload($upload)
{
    // Basic structural validation.
    if (!is_array($upload) || empty($upload['file']) || empty($upload['type']) || empty($upload['url'])) {
        return $upload; // Unexpected structure – bail.
    }

    $allowed_mimes = array('image/jpeg', 'image/png');
    if (!in_array($upload['type'], $allowed_mimes, true)) {
        return $upload; // Not a target mime type.
    }

    $file_path = $upload['file'];
    if (!is_string($file_path) || !file_exists($file_path) || !is_readable($file_path)) {
        return $upload; // File not accessible.
    }

    // Ensure file really is what it claims to be (prevents spoofed extension/mime).
    $wp_check = wp_check_filetype_and_ext($file_path, basename($file_path));
    if (!empty($wp_check['ext']) && !empty($wp_check['type']) && !in_array($wp_check['type'], $allowed_mimes, true)) {
        return $upload; // Mismatch.
    }

    // Guard: ensure path resides inside uploads dir.
    $uploads_dir = wp_get_upload_dir();
    if (empty($uploads_dir['basedir']) || strpos(realpath($file_path) ?: '', realpath($uploads_dir['basedir']) ?: '') !== 0) {
        return $upload; // Outside uploads directory.
    }

    $file_info = pathinfo($file_path);
    if (empty($file_info['dirname']) || empty($file_info['filename']) || empty($file_info['extension'])) {
        return $upload;
    }

    $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';

    // If WebP already exists (re-upload / regeneration), skip to avoid overwrite.
    if (file_exists($webp_path)) {
        return $upload;
    }

    // Optional memory safety check (approximate): width * height * 5 bytes.
    $dimensions = @getimagesize($file_path);
    $width = $height = 0;
    if (is_array($dimensions) && isset($dimensions[0], $dimensions[1])) {
        $width  = (int) $dimensions[0];
        $height = (int) $dimensions[1];
        $estimated = $width * $height * 5; // generous per-pixel multiplier for memory estimate.
        if (function_exists('wp_convert_hr_to_bytes')) {
            $limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
            if ($limit > 0 && $estimated > ($limit * 0.8)) { // exceed 80% of limit – bail to avoid fatal.
                return $upload;
            }
        }
    }

    $image = false;
    switch ($upload['type']) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file_path);
            if ($image && function_exists('exif_read_data') && is_readable($file_path)) {
                $exif = @exif_read_data($file_path); // Still suppress here; some files emit warnings.
                if (isset($exif['Orientation'])) {
                    switch ((int)$exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }
                }
            }
            break;
        case 'image/png':
            $image = imagecreatefrompng($file_path);
            if ($image) {
                if (function_exists('imagepalettetotruecolor')) {
                    @imagepalettetotruecolor($image); // PHP < 5.5 compatibility guard is obsolete but safe.
                }
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
    }

    if (!$image || !function_exists('imagewebp')) {
        if (is_resource($image) || $image instanceof GdImage) {
            imagedestroy($image);
        }
        return $upload;
    }

    // Resize if exceeding configured max dimensions (scale down preserving aspect ratio).
    $max_w = spx_webp_converter_get_max_width();
    $max_h = spx_webp_converter_get_max_height();
    if (($max_w > 0 || $max_h > 0) && $width > 0 && $height > 0) {
        $target_w = $width;
        $target_h = $height;
        if ($max_w > 0 && $target_w > $max_w) {
            $ratio = $max_w / $target_w;
            $target_w = $max_w;
            $target_h = (int) round($target_h * $ratio);
        }
        if ($max_h > 0 && $target_h > $max_h) {
            $ratio = $max_h / $target_h;
            $target_h = $max_h;
            $target_w = (int) round($target_w * $ratio);
        }
        if ($target_w > 0 && $target_h > 0 && ($target_w !== $width || $target_h !== $height)) {
            $resized = imagecreatetruecolor($target_w, $target_h);
            // Preserve alpha.
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
            if (imagecopyresampled($resized, $image, 0, 0, 0, 0, $target_w, $target_h, $width, $height)) {
                imagedestroy($image);
                $image = $resized;
                $width = $target_w;
                $height = $target_h;
            } else {
                imagedestroy($resized); // fall back to original size on failure
            }
        }
    }

    // Write WebP with quality 80; ensure directory writable.
    if (!is_writable($file_info['dirname'])) {
        imagedestroy($image);
        return $upload;
    }

    $quality = (int) apply_filters('spx_webp_converter_quality', spx_webp_converter_get_quality());
    $success = imagewebp($image, $webp_path, $quality);
    imagedestroy($image);
    if (!$success || !file_exists($webp_path)) {
        return $upload; // Keep original on failure.
    }

    // Always replace original now for consistency; optionally keep original via filter.
    $replace = (bool) apply_filters('spx_webp_converter_replace_original', true, $file_path, $webp_path);
    if ($replace && is_writable($file_path)) {
        @unlink($file_path);
        $upload['file'] = $webp_path;
        $upload['url']  = preg_replace('/\.' . preg_quote($file_info['extension'], '/') . '$/i', '.webp', $upload['url']);
        $upload['type'] = 'image/webp';
    }

    return $upload;
}
add_filter('wp_handle_upload', 'spx_webp_converter_convert_image_to_webp_on_upload');
