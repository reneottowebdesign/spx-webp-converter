<?php
/**
 * Conversion logic extracted into class.
 */

if (!defined('ABSPATH')) { exit; }

class SPX_WebP_Converter_Converter {
    /**
     * Hook callback for wp_handle_upload.
     * @param array $upload
     * @return array
     */
    public static function convert_upload(array $upload): array {
        if (!is_array($upload) || empty($upload['file']) || empty($upload['type']) || empty($upload['url'])) {
            return $upload;
        }
        $allowed = ['image/jpeg','image/png'];
        if (!in_array($upload['type'], $allowed, true)) { return $upload; }
        $file_path = $upload['file'];
        if (!is_string($file_path) || !file_exists($file_path) || !is_readable($file_path)) { return $upload; }

        $wp_check = wp_check_filetype_and_ext($file_path, basename($file_path));
        if (!empty($wp_check['ext']) && !empty($wp_check['type']) && !in_array($wp_check['type'], $allowed, true)) { return $upload; }

        $uploads_dir = wp_get_upload_dir();
        if (empty($uploads_dir['basedir']) || strpos(realpath($file_path) ?: '', realpath($uploads_dir['basedir']) ?: '') !== 0) { return $upload; }

        $info = pathinfo($file_path);
        if (empty($info['dirname']) || empty($info['filename']) || empty($info['extension'])) { return $upload; }
        $webp_path = $info['dirname'] . '/' . $info['filename'] . '.webp';
        if (file_exists($webp_path)) { return $upload; }

        $dimensions = @getimagesize($file_path);
        $width = $height = 0;
        if (is_array($dimensions) && isset($dimensions[0], $dimensions[1])) {
            $width  = (int) $dimensions[0];
            $height = (int) $dimensions[1];
            $estimated = $width * $height * 5;
            if (function_exists('wp_convert_hr_to_bytes')) {
                $limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
                if ($limit > 0 && $estimated > ($limit * 0.8)) { return $upload; }
            }
        }

        $image = null;
        if ($upload['type'] === 'image/jpeg') {
            $image = imagecreatefromjpeg($file_path);
            if ($image && function_exists('exif_read_data') && is_readable($file_path)) {
                $exif = @exif_read_data($file_path);
                if (isset($exif['Orientation'])) {
                    switch ((int)$exif['Orientation']) {
                        case 3: $image = imagerotate($image, 180, 0); break;
                        case 6: $image = imagerotate($image, -90, 0); break;
                        case 8: $image = imagerotate($image, 90, 0); break;
                    }
                }
            }
        } elseif ($upload['type'] === 'image/png') {
            $image = imagecreatefrompng($file_path);
            if ($image) {
                if (function_exists('imagepalettetotruecolor')) { @imagepalettetotruecolor($image); }
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
        }

        if (!$image || !function_exists('imagewebp')) {
            if (is_resource($image) || $image instanceof GdImage) { imagedestroy($image); }
            return $upload;
        }

        // Resize one-pass
        $max_w = spx_webp_converter_get_max_width();
        $max_h = spx_webp_converter_get_max_height();
        if (($max_w > 0 || $max_h > 0) && $width > 0 && $height > 0) {
            $scale_w = $max_w > 0 ? ($max_w / $width) : 1;
            $scale_h = $max_h > 0 ? ($max_h / $height) : 1;
            $scale = min($scale_w, $scale_h, 1);
            if ($scale < 1) {
                $target_w = (int) max(1, round($width * $scale));
                $target_h = (int) max(1, round($height * $scale));
                $resized = imagecreatetruecolor($target_w, $target_h);
                imagealphablending($resized, false); imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 0,0,0,127); imagefill($resized,0,0,$transparent);
                if (imagecopyresampled($resized, $image,0,0,0,0,$target_w,$target_h,$width,$height)) {
                    imagedestroy($image); $image = $resized; $width=$target_w; $height=$target_h;
                } else { imagedestroy($resized); }
            }
        }

        if (!is_writable($info['dirname'])) { imagedestroy($image); return $upload; }
        $quality = (int) apply_filters('spx_webp_converter_quality', spx_webp_converter_get_quality());
        $success = imagewebp($image, $webp_path, $quality);
        imagedestroy($image);
        if (!$success || !file_exists($webp_path)) { return $upload; }

        $replace = (bool) apply_filters('spx_webp_converter_replace_original', true, $file_path, $webp_path);
        if ($replace && is_writable($file_path)) {
            @unlink($file_path);
            $upload['file'] = $webp_path;
            $upload['url'] = preg_replace('/\.' . preg_quote($info['extension'], '/') . '$/i', '.webp', $upload['url']);
            $upload['type'] = 'image/webp';
        }
        return $upload;
    }
}
