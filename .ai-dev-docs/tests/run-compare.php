<?php
// Run admin compare without deprecation noise
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

if ( ! function_exists( 'wp_set_current_user' ) ) {
    echo json_encode( array( 'error' => 'WP functions not loaded' ) );
    exit(1);
}

wp_set_current_user(1);
$instance = Newebpay_WooCommerce_Blocks_Integration::get_instance();
$req = new WP_REST_Request();
$resp = $instance->rest_admin_compare_payment_methods($req);
echo wp_json_encode( $resp->get_data() );
