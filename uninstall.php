<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
delete_option( 'wta_openai_api_key' );
delete_option( 'wta_analysis_data' );

