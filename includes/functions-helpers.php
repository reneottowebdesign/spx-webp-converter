<?php

/**
 * Helper functions for SPX WebP Converter.
 */

if (!defined('ABSPATH')) {
    exit;
}

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

function spx_webp_converter_get_max_width(): int
{
    $w = (int) get_option('spx_webp_converter_max_width', 0);
    return $w < 0 ? 0 : $w;
}

function spx_webp_converter_get_max_height(): int
{
    $h = (int) get_option('spx_webp_converter_max_height', 0);
    return $h < 0 ? 0 : $h;
}
