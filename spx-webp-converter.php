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
    $image_mime_types = array('image/jpeg', 'image/png');

    // Only convert JPEG and PNG files.
    if (!in_array($upload['type'], $image_mime_types, true)) {
        return $upload;
    }

    $file_path = $upload['file'];
    $file_info = pathinfo($file_path);

    $webp_path = $file_info['dirname'] . '/' . $file_info['filename'] . '.webp';
    // Create image resource depending on mime type.
    switch ($upload['type']) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($file_path);
            // Try to correct orientation for JPEG if EXIF data is available.
            if ($image && function_exists('exif_read_data')) {
                $exif = @exif_read_data($file_path);
                if (isset($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
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
            $image = @imagecreatefrompng($file_path);
            // Preserve transparency for PNG.
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        default:
            $image = false; // Should not happen due to earlier check.
    }

    if (!$image) {
        // Failed to create image resource, abort conversion.
        return $upload;
    }

    // If conversion works, save and replace.
    if (function_exists('imagewebp')) {
        if (imagewebp($image, $webp_path, 80)) { // Quality: 0–100
            imagedestroy($image);

            // Remove original image only after successful WebP creation.
            @unlink($file_path);

            // Update upload array to reflect new WebP file.
            $upload['file'] = $webp_path;
            $upload['url']  = str_replace('.' . $file_info['extension'], '.webp', $upload['url']);
            $upload['type'] = 'image/webp';
        } else {
            // If conversion failed, free resource and keep original.
            imagedestroy($image);
        }
    }

    return $upload;
}
add_filter('wp_handle_upload', 'spx_webp_converter_convert_image_to_webp_on_upload');
