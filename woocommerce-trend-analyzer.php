<?php
/**
 * Plugin Name: WooCommerce Trend Analyzer
 * Description: Analiza las tendencias de productos en WooCommerce utilizando ChatGPT.
 * Version: 1.1
 * Author: Manuel Peña Ramos
 * Text Domain: woocommerce-trend-analyzer
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

require_once WTA_PLUGIN_DIR . 'includes/php-mo.php';

register_activation_hook( __FILE__, 'wta_generate_mo_files' );

/**
 * Compile translation files on plugin activation.
 */
function wta_generate_mo_files() {
    $languages = [ 'es_ES', 'en_US' ];
    $lang_dir  = plugin_dir_path( __FILE__ ) . 'languages/';

    foreach ( $languages as $lang ) {
        $po = $lang_dir . "woocommerce-trend-analyzer-{$lang}.po";
        $mo = $lang_dir . "woocommerce-trend-analyzer-{$lang}.mo";

        if ( file_exists( $po ) ) {
            wta_compile_po_to_mo( $po, $mo );
        }
    }
    if ( ! get_option( 'wta_language' ) ) {
        update_option( 'wta_language', 'es_ES' );
    }
}

/**
 * Load selected language for plugin translations.
 */
function wta_load_selected_language() {
    $lang = get_option( 'wta_language', 'es_ES' );
    if ( isset( $_GET['wta_lang'] ) && in_array( $_GET['wta_lang'], [ 'es_ES', 'en_US' ], true ) ) {
        $lang = sanitize_text_field( $_GET['wta_lang'] );
        update_option( 'wta_language', $lang );
    }

    $lang_dir = WTA_PLUGIN_DIR . 'languages/';
    $po       = $lang_dir . "woocommerce-trend-analyzer-{$lang}.po";
    $mo       = $lang_dir . "woocommerce-trend-analyzer-{$lang}.mo";

    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log(
            sprintf(
                'WTA lang switch request: %s | PO: %s (%s) | MO: %s (%s)',
                $lang,
                $po,
                file_exists( $po ) ? 'exists' : 'missing',
                $mo,
                file_exists( $mo ) ? 'exists' : 'missing'
            )
        );
    }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( 'WTA post-compile MO exists: %s', file_exists( $mo ) ? 'yes' : 'no' ) );
        }

    if ( ! file_exists( $mo ) && file_exists( $po ) ) {
        wta_compile_po_to_mo( $po, $mo );
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
    WooCommerce_Trend_Analyzer::get_instance();
}
add_action( 'plugins_loaded', 'wta_init' );
