<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
delete_option( 'stai_openai_api_key' );
delete_option( 'stai_analysis_data' );

