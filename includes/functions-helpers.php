<?php

/**
 * Helper functions for SPX WebP Converter.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Get the configured WebP conversion quality.
 *
 * @since 1.1.0
 * @access public
 * @return int Quality value between 0 and 100.
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
 * Get the configured maximum image width.
 *
 * @since 1.1.0
 * @access public
 * @return int Maximum width in pixels, 0 for unlimited.
 */
function spx_webp_converter_get_max_width(): int
{
    $w = (int) get_option('spx_webp_converter_max_width', 0);
    return $w < 0 ? 0 : $w;
}

/**
 * Get the configured maximum image height.
 *
 * @since 1.1.0
 * @access public
 * @return int Maximum height in pixels, 0 for unlimited.
 */
function spx_webp_converter_get_max_height(): int
{
    $h = (int) get_option('spx_webp_converter_max_height', 0);
    return $h < 0 ? 0 : $h;
}
