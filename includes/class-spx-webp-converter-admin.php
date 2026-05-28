<?php

/**
 * Admin settings handling for SPX WebP Converter.
 */

if (! defined('ABSPATH')) {
    exit;
}

class SPX_WebP_Converter_Admin
{

    /**
     * Initialize admin hooks.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function init(): void
    {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_action('admin_menu', array(__CLASS__, 'add_settings_page'));
    }

    /**
     * Register plugin settings, sections and fields.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function register_settings(): void
    {
        register_setting(
            'spx_webp_converter_settings',
            'spx_webp_converter_quality',
            array(
                'type'              => 'integer',
                'sanitize_callback' => array(__CLASS__, 'sanitize_quality'),
                'default'           => 80,
                'show_in_rest'      => true,
            )
        );

        foreach (array('width', 'height') as $axis) {
            register_setting(
                'spx_webp_converter_settings',
                'spx_webp_converter_max_' . $axis,
                array(
                    'type'              => 'integer',
                    'sanitize_callback' => array(__CLASS__, 'sanitize_dimension'),
                    'default'           => 0,
                    'show_in_rest'      => true,
                )
            );
        }

        add_settings_section(
            'spx_webp_converter_main',
            __('WebP Conversion Settings', 'spx-webp-converter'),
            array(__CLASS__, 'render_main_section'),
            'spx_webp_converter'
        );

        add_settings_field(
            'spx_webp_converter_quality',
            __('Quality (0-100)', 'spx-webp-converter'),
            array(__CLASS__, 'render_quality_field'),
            'spx_webp_converter',
            'spx_webp_converter_main'
        );

        add_settings_field(
            'spx_webp_converter_max_width',
            __('Max Width (px, 0 = unlimited)', 'spx-webp-converter'),
            array(__CLASS__, 'render_max_width_field'),
            'spx_webp_converter',
            'spx_webp_converter_main'
        );

        add_settings_field(
            'spx_webp_converter_max_height',
            __('Max Height (px, 0 = unlimited)', 'spx-webp-converter'),
            array(__CLASS__, 'render_max_height_field'),
            'spx_webp_converter',
            'spx_webp_converter_main'
        );
    }

    /**
     * Render the main settings section description.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function render_main_section(): void
    {
        echo '<p>' . esc_html__('Configure conversion quality and optional maximum dimensions. Leave max dimensions at 0 for no limit.', 'spx-webp-converter') . '</p>';
    }

    /**
     * Render the quality settings field.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function render_quality_field(): void
    {
        printf(
            '<input type="number" min="0" max="100" name="spx_webp_converter_quality" value="%d" class="small-text" />',
            esc_attr(spx_webp_converter_get_quality())
        );
    }

    /**
     * Render the max width settings field.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function render_max_width_field(): void
    {
        printf(
            '<input type="number" min="0" name="spx_webp_converter_max_width" value="%d" class="small-text" />',
            esc_attr(spx_webp_converter_get_max_width())
        );
    }

    /**
     * Render the max height settings field.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function render_max_height_field(): void
    {
        printf(
            '<input type="number" min="0" name="spx_webp_converter_max_height" value="%d" class="small-text" />',
            esc_attr(spx_webp_converter_get_max_height())
        );
    }

    /**
     * Add settings page to the WordPress admin menu.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function add_settings_page(): void
    {
        add_options_page(
            __('SPX WebP Converter', 'spx-webp-converter'),
            __('SPX WebP Converter', 'spx-webp-converter'),
            'manage_options',
            'spx_webp_converter',
            array(__CLASS__, 'render_settings_page')
        );
    }

    /**
     * Render the plugin settings page.
     *
     * @since 1.1.0
     * @access public
     * @return void
     */
    public static function render_settings_page(): void
    {
        if (! current_user_can('manage_options')) {
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
     * Sanitize the quality setting value.
     *
     * @since 1.1.0
     * @access public
     * @param mixed $value Raw input value.
     * @return int Sanitized quality between 0 and 100.
     */
    public static function sanitize_quality($value): int
    {
        $value = (int) $value;
        if ($value < 0) {
            $value = 0;
        } elseif ($value > 100) {
            $value = 100;
        }
        return $value;
    }

    /**
     * Sanitize a dimension setting value.
     *
     * @since 1.1.0
     * @access public
     * @param mixed $value Raw input value.
     * @return int Sanitized dimension, minimum 0.
     */
    public static function sanitize_dimension($value): int
    {
        $value = (int) $value;
        return $value < 0 ? 0 : $value;
    }
}
