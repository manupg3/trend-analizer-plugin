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

/**
 * Initialize plugin.
 */
function wta_init() {
    load_plugin_textdomain( 'woocommerce-trend-analyzer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    WooCommerce_Trend_Analyzer::get_instance();
}
add_action( 'plugins_loaded', 'wta_init' );
