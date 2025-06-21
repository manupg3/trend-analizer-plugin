<?php
/**
 * Plugin Name: WooCommerce Trend Analyzer
 * Description: Analiza las tendencias de productos en WooCommerce utilizando ChatGPT.
 * Version: 1.1
 * Author: Manuel Peña Ramos
 * Text Domain: woocommerce-trend-analyzer
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

use Gettext\Translations;
use Gettext\Generators\Mo;

register_activation_hook( __FILE__, 'wta_generate_mo_files' );

/**
 * Compile translation files on plugin activation.
 */
function wta_generate_mo_files() {
    if ( ! class_exists( '\\Gettext\\Translations' ) ) {
        return;
    }

    $languages = [ 'es_ES', 'en_US' ];
    $lang_dir  = plugin_dir_path( __FILE__ ) . 'languages/';

    foreach ( $languages as $lang ) {
        $po = $lang_dir . "woocommerce-trend-analyzer-{$lang}.po";
        $mo = $lang_dir . "woocommerce-trend-analyzer-{$lang}.mo";

        if ( file_exists( $po ) ) {
            $translations = Translations::fromPoFile( $po );
            Mo::toFile( $translations, $mo );
        }
    }
}

/**
 * Initialize plugin.
 */
function wta_init() {
    load_plugin_textdomain( 'woocommerce-trend-analyzer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    WooCommerce_Trend_Analyzer::get_instance();
}
add_action( 'plugins_loaded', 'wta_init' );
