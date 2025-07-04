<?php
/**
 * Plugin Name: Sales Trend Analyzer
 * Description: Analiza las tendencias de productos en WooCommerce utilizando ChatGPT.
 * Version:     1.1
 * Author:      Manuel Peña Ramos
 * Text Domain: sales-trend-analyzer-ia
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// -----------------------------
// 1) Definición de constantes
// -----------------------------
if ( ! defined( 'STAI_VERSION' ) ) {
    define( 'STAI_VERSION', '1.1' );
}
if ( ! defined( 'STAI_PLUGIN_DIR' ) ) {
    define( 'STAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'STAI_PLUGIN_URL' ) ) {
    define( 'STAI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// ----------------------------------------
// 2) Carga de ficheros de traducción (.mo)
// ----------------------------------------
add_action( 'init', function(){
    load_plugin_textdomain(
        'sales-trend-analyzer-ia',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
} );

// ----------------------------------------------------------------
// 3) Marca la necesidad de setup al activar (si no hay idioma fijo)
// ----------------------------------------------------------------
function stai_set_setup_flag() {
    if ( empty( get_option( 'stai_language' ) ) ) {
        update_option( 'stai_setup_needed', 1 );
    }
}
register_activation_hook( __FILE__, 'stai_set_setup_flag' );

// ---------------------------------------------------------------
// 4) Limpieza de opciones al desactivar (idioma y flag de setup)
// ---------------------------------------------------------------
function stai_cleanup_on_deactivate() {
    delete_option( 'stai_language' );
    delete_option( 'stai_setup_needed' );
}
register_deactivation_hook( __FILE__, 'stai_cleanup_on_deactivate' );

// ----------------------------------------------------
// 5) Captura cambio de idioma vía GET y guarda opción
// ----------------------------------------------------
add_action( 'admin_init', function(){
    if (
        isset( $_GET['stai_lang'], $_GET['stai_lang_nonce'] )
        && wp_verify_nonce( $_GET['stai_lang_nonce'], 'stai_set_language' )
        && in_array( $_GET['stai_lang'], [ 'es_ES', 'en_US' ], true )
    ) {
        update_option( 'stai_language', sanitize_text_field( $_GET['stai_lang'] ) );
        delete_option( 'stai_setup_needed' );
        wp_safe_redirect( remove_query_arg( [ 'stai_lang', 'stai_lang_nonce' ] ) );
        exit;
    }
} );

// ----------------------------------------------------
// 6) Aviso si WooCommerce no está activo
// ----------------------------------------------------
function stai_missing_wc_notice() {
    echo '<div class="notice notice-error"><p>'
       . esc_html__( 'WooCommerce Trend Analyzer requires WooCommerce to be active.', 'sales-trend-analyzer-ia' )
       . '</p></div>';
}

// ----------------------------------------------------
// 7) Incluye la clase principal y arranca el plugin
// ----------------------------------------------------
require_once STAI_PLUGIN_DIR . 'includes/class-stai-trend-analyzer.php';

add_action( 'plugins_loaded', function(){
    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', 'stai_missing_wc_notice' );
        return;
    }
    STAI_Trend_Analyzer::get_instance();
} );
