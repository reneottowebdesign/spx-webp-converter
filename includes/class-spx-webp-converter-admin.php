<?php
/**
 * Admin settings handling for SPX WebP Converter.
 */

if (!defined('ABSPATH')) { exit; }

class SPX_WebP_Converter_Admin {
    public static function init(): void {
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_menu', [__CLASS__, 'add_settings_page']);
    }

    public static function register_settings(): void {
        register_setting(
            'spx_webp_converter_settings',
            'spx_webp_converter_quality',
            [
                'type' => 'integer',
                'sanitize_callback' => [__CLASS__, 'sanitize_quality'],
                'default' => 80,
            ]
        );

        foreach (['width','height'] as $axis) {
            register_setting(
                'spx_webp_converter_settings',
                'spx_webp_converter_max_' . $axis,
                [
                    'type' => 'integer',
                    'sanitize_callback' => [__CLASS__, 'sanitize_dimension'],
                    'default' => 0,
                ]
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
                printf('<input type="number" min="0" max="100" name="spx_webp_converter_quality" value="%d" class="small-text" />', esc_attr(spx_webp_converter_get_quality()));
            },
            'spx_webp_converter',
            'spx_webp_converter_main'
        );

        add_settings_field(
            'spx_webp_converter_max_width',
            __('Max Width (px, 0 = unlimited)', 'spx-webp-converter'),
            function () {
                printf('<input type="number" min="0" name="spx_webp_converter_max_width" value="%d" class="small-text" />', esc_attr(spx_webp_converter_get_max_width()));
            },
            'spx_webp_converter',
            'spx_webp_converter_main'
        );

        add_settings_field(
            'spx_webp_converter_max_height',
            __('Max Height (px, 0 = unlimited)', 'spx-webp-converter'),
            function () {
                printf('<input type="number" min="0" name="spx_webp_converter_max_height" value="%d" class="small-text" />', esc_attr(spx_webp_converter_get_max_height()));
            },
            'spx_webp_converter',
            'spx_webp_converter_main'
        );
    }

    public static function add_settings_page(): void {
        add_options_page(
            __('SPX WebP Converter', 'spx-webp-converter'),
            __('SPX WebP Converter', 'spx-webp-converter'),
            'manage_options',
            'spx_webp_converter',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function render_settings_page(): void {
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

    public static function sanitize_quality($value): int {
        $value = (int) $value;
        if ($value < 0) { $value = 0; } elseif ($value > 100) { $value = 100; }
        return $value;
    }

    public static function sanitize_dimension($value): int {
        $value = (int) $value;
        return $value < 0 ? 0 : $value;
    }
}
