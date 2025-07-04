<?php
/**
 * Plugin Name: Sales Trend Analyzer
 * Description: Analiza las tendencias de productos en WooCommerce utilizando ChatGPT.
 * Version: 1.1
 * Author: Manuel Peña Ramos
 * Text Domain: sales-trend-analyzer-ia
 * Domain Path: /languages
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
if ( ! defined( 'STAI_VERSION' ) ) {
    define( 'STAI_VERSION', '1.1' );
}

if ( ! defined( 'STAI_PLUGIN_DIR' ) ) {
    define( 'STAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'STAI_PLUGIN_URL' ) ) {
    define( 'STAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once STAI_PLUGIN_DIR . 'includes/class-stai-trend-analyzer.php';

/**
 * Flag setup on activation if language is not configured.
 */
function stai_set_setup_flag() {
    if ( false === get_option( 'stai_language', false ) ) {
        add_option( 'stai_setup_needed', 1, '', false );
    }
}
register_activation_hook( __FILE__, 'stai_set_setup_flag' );

/**
 * Redirect to setup page right after activation.
 */
function stai_maybe_redirect_setup() {
    if ( get_option( 'stai_setup_needed' ) ) {
        delete_option( 'stai_setup_needed' );
        if ( ! isset( $_GET['page'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=trend-analyzer&setup=1' ) );
            exit;
        }
    }
}
add_action( 'admin_init', 'stai_maybe_redirect_setup' );

// Translation files (.mo) are expected to be present in the languages folder.
// They are not generated automatically by the plugin.

/**
 * Load selected language for plugin translations.
 */
function stai_load_selected_language() {
    $lang = get_option( 'stai_language', false );

    if ( isset( $_GET['stai_lang'] ) && isset( $_GET['stai_lang_nonce'] ) &&
         wp_verify_nonce( $_GET['stai_lang_nonce'], 'stai_set_language' ) &&
         in_array( $_GET['stai_lang'], [ 'es_ES', 'en_US' ], true ) && ! $lang ) {
        $lang = sanitize_text_field( $_GET['stai_lang'] );
        if ( current_user_can( 'manage_options' ) ) {
            update_option( 'stai_language', $lang );
        }
    }

    if ( ! $lang ) {
        $lang = 'en_US';
    }

    $lang_dir = STAI_PLUGIN_DIR . 'languages/';
    $mo       = $lang_dir . "sales-trend-analyzer-ia-{$lang}.mo";

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log(
            sprintf(
                'STAI lang switch request: %s | MO: %s (%s)',
                $lang,
                $mo,
                file_exists( $mo ) ? 'exists' : 'missing'
            )
        );
    }

    $mofile = $mo;
    unload_textdomain( 'sales-trend-analyzer-ia' );
    load_textdomain( 'sales-trend-analyzer-ia', $mofile );

    return $lang;
}

/**
 * Initialize plugin.
 */
function stai_init() {
    $locale = stai_load_selected_language();

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'stai_missing_wc_notice' );
        return;
    }

    STAI_Trend_Analyzer::get_instance();
}

/**
 * Display admin notice if WooCommerce is not active.
 */
function stai_missing_wc_notice() {
    echo '<div class="notice notice-error"><p>' .
        esc_html__( 'WooCommerce Trend Analyzer requires WooCommerce to be active.', 'sales-trend-analyzer-ia' ) .
        '</p></div>';
}
add_action( 'plugins_loaded', 'stai_init' );
