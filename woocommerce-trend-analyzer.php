<?php
/**
 * Plugin Name: Sales Trend Analyzer
 * Description: Analiza las tendencias de productos en WooCommerce utilizando ChatGPT.
 * Version: 1.1
 * Author: Manuel Peña Ramos
 * Text Domain: trend-analizer-plugin-main
 * Domain Path: /languages
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
if ( ! defined( 'WTA_VERSION' ) ) {
    define( 'WTA_VERSION', '1.1' );
}

if ( ! defined( 'WTA_PLUGIN_DIR' ) ) {
    define( 'WTA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WTA_PLUGIN_URL' ) ) {
    define( 'WTA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once WTA_PLUGIN_DIR . 'includes/class-woocommerce-trend-analyzer.php';

// Translation files (.mo) are expected to be present in the languages folder.
// They are not generated automatically by the plugin.

/**
 * Load selected language for plugin translations.
 */
function wta_load_selected_language() {
    $lang = get_option( 'wta_language', 'es_ES' );
    if ( isset( $_GET['wta_lang'] ) && in_array( $_GET['wta_lang'], [ 'es_ES', 'en_US' ], true ) ) {
        $lang = sanitize_text_field( $_GET['wta_lang'] );
        if ( current_user_can( 'manage_options' ) ) {
            update_option( 'wta_language', $lang );
        }
    }

    $lang_dir = WTA_PLUGIN_DIR . 'languages/';
    $mo       = $lang_dir . "woocommerce-trend-analyzer-{$lang}.mo";

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log(
            sprintf(
                'WTA lang switch request: %s | MO: %s (%s)',
                $lang,
                $mo,
                file_exists( $mo ) ? 'exists' : 'missing'
            )
        );
    }

    $mofile = $mo;
    unload_textdomain( 'woocommerce-trend-analyzer' );
    load_textdomain( 'woocommerce-trend-analyzer', $mofile );

    return $lang;
}

/**
 * Initialize plugin.
 */
function wta_init() {
    $locale = wta_load_selected_language();

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'wta_missing_wc_notice' );
        return;
    }

    WooCommerce_Trend_Analyzer::get_instance();
}

/**
 * Display admin notice if WooCommerce is not active.
 */
function wta_missing_wc_notice() {
    echo '<div class="notice notice-error"><p>' .
        esc_html__( 'WooCommerce Trend Analyzer requires WooCommerce to be active.', 'trend-analizer-plugin-main' ) .
        '</p></div>';
}
add_action( 'plugins_loaded', 'wta_init' );
