<?php


/**
 * Plugin Name: Newebpay Payment
 * Plugin URI: http://www.newebpay.com/
 * Description: NewebPay Payment for WooCommerce
 * Version: 1.0.10
 * Author: Neweb Technologies Co., Ltd.
 * Author URI: https://www.newebpay.com/website/Page/content/download_api#2
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.7
 * Tested up to: 6.8
 * Requires PHP: 8.0
 * WC requires at least: 8.0
 * WC tested up to: 10.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NEWEB_MAIN_PATH', dirname( __FILE__ ) );

// Load Logger
require_once NEWEB_MAIN_PATH . '/includes/class-newebpay-logger.php';

// To enable High-Performance Order Storage
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

if ( ! class_exists( 'WC_Newebpay_Payment' ) ) {

	class WC_Newebpay_Payment {


		private static $instance;

		/**
		 * Returns the *Singleton* instance of this class.
		 *
		 * @return Singleton The *Singleton* instance.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		protected function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		public function init() {
			$this->init_gateways();
		}

		private function init_gateways() {

            if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
                return;
            }

			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_newebpay_gateway' ) );

			$this->init_modules();
		}

		/**
		 * Add the gateway to WooCommerce
		 *
		 * @access public
		 * @param array $methods
		 * @package     WooCommerce/Classes/Payment
		 * @return array
		 */
		public function add_newebpay_gateway( $methods ) {
			$methods[] = 'WC_newebpay';
			return $methods;
		}

		private function init_modules() {
			include_once NEWEB_MAIN_PATH . '/includes/nwpenc/encProcess.php';
			include_once NEWEB_MAIN_PATH . '/includes/nwp/nwpMPG.php';
			include_once NEWEB_MAIN_PATH . '/includes/invoice/nwpElectronicInvoice.php';
			include_once NEWEB_MAIN_PATH . '/includes/api/nwpOthersAPI.php';
		}
	}

	$GLOBALS['wc_newebpay_payment'] = WC_Newebpay_Payment::get_instance();

}
