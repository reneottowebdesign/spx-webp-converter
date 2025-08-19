<?php

/**
 * Plugin Name: SPX - Webp Converter
 * Description: Konvertiert JPEG/PNG-Uploads automatisch in WebP und erlaubt WebP-Uploads.
 * Version: 1.0.0
 * Author: René Otto
 * License: GPLv2 or later
 * Text Domain: spx-webp-converter
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
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
    if (is_array($dimensions) && isset($dimensions[0], $dimensions[1])) {
        $estimated = (int)$dimensions[0] * (int)$dimensions[1] * 5; // generous per-pixel multiplier.
        if (function_exists('wp_convert_hr_to_bytes')) {
            $limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
            if ($limit > 0 && $estimated > ($limit * 0.6)) { // exceed 60% of limit – skip.
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

    // Write WebP with quality 80; ensure directory writable.
    if (!is_writable($file_info['dirname'])) {
        imagedestroy($image);
        return $upload;
    }

    $success = imagewebp($image, $webp_path, 80);
    imagedestroy($image);
    if (!$success || !file_exists($webp_path)) {
        return $upload; // Keep original on failure.
    }

    // Only delete original if new file smaller (safety + disk usage win).
    $orig_size = filesize($file_path) ?: 0;
    $webp_size = filesize($webp_path) ?: PHP_INT_MAX;
    if ($webp_size < $orig_size && is_writable($file_path)) {
        @unlink($file_path);
        $upload['file'] = $webp_path;
        $upload['url']  = preg_replace('/\.' . preg_quote($file_info['extension'], '/') . '$/i', '.webp', $upload['url']);
        $upload['type'] = 'image/webp';
    } else {
        // If WebP is larger, keep original and remove generated WebP to avoid clutter.
        @unlink($webp_path);
    }

    return $upload;
}
add_filter('wp_handle_upload', 'spx_webp_converter_convert_image_to_webp_on_upload');
