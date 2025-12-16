<?php


/**
 * Plugin Name: Newebpay Payment
 * Plugin URI: http://www.newebpay.com/
 * Description: NewebPay Payment for WooCommerce
 * Version: 1.0.11
 * Author: Neweb Technologies Co., Ltd.
 * Author URI: https://www.newebpay.com/website/Page/content/download_api#2
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Requires at least: 6.7
 * Tested up to: 6.8
 * Requires PHP: 8.0
 * Requires Plugins: woocommerce
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
		private $logger;

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
			// 藍新回傳常為跨站 POST，可能因 SameSite 導致登入/Session cookie 未附帶。
			// 這裡用 PRG（Post/Redirect/Get）在 template_redirect 階段先處理回傳並 redirect 到同 URL 的 GET，確保 cookie 能正常帶上。
			add_action( 'template_redirect', array( $this, 'newebpay_prg_template_redirect' ), 1 );
			// 載入按鈕樣式（移除預設 focus 狀態）
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_button_styles' ) );
			$this->logger = class_exists( 'Newebpay_Logger' ) ? Newebpay_Logger::get_instance() : null;
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

		/**
		 * 載入按鈕樣式 CSS 和 JavaScript（移除預設 focus 狀態）
		 */
		public function enqueue_button_styles() {
			if ( is_order_received_page() || is_view_order_page() || is_checkout() ) {
				$plugin_url = plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' );
				wp_enqueue_style(
					'newebpay-buttons',
					$plugin_url . 'assets/css/newebpay-buttons.css',
					array(),
					'1.0.3'
				);
				wp_enqueue_script(
					'newebpay-buttons',
					$plugin_url . 'assets/js/public/newebpay-buttons.js',
					array(),
					'1.0.1',
					true
				);
			}
		}

		/**
		 * 在 template_redirect 階段處理藍新幕前回傳（TradeSha/TradeInfo）並 redirect 到 GET。
		 * 目的：避免跨站 POST 時 cookie 沒帶，導致無法識別登入狀態/權限不一致。
		 */
		public function newebpay_prg_template_redirect() {
			if ( ! function_exists( 'wc_get_order' ) ) {
				return;
			}

			$order_id = absint( get_query_var( 'order-received' ) );
			if ( ! $order_id ) {
				return;
			}

			if ( ( $_SERVER['REQUEST_METHOD'] ?? '' ) !== 'POST' ) {
				return;
			}

			// 有些回傳型態可能導致 $_POST 不完整，這裡用 $_REQUEST 判斷
			if ( empty( $_REQUEST['TradeSha'] ) || empty( $_REQUEST['TradeInfo'] ) ) {
				return;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}

			// 只處理藍新金流
			if ( method_exists( $order, 'get_payment_method' ) && $order->get_payment_method() !== 'newebpay' ) {
				return;
			}

			$settings = get_option( 'woocommerce_newebpay_settings', array() );
			$hash_key = isset( $settings['HashKey'] ) ? trim( (string) $settings['HashKey'] ) : '';
			$hash_iv  = isset( $settings['HashIV'] ) ? trim( (string) $settings['HashIV'] ) : '';

			if ( empty( $hash_key ) || empty( $hash_iv ) || ! class_exists( 'encProcess' ) ) {
				return;
			}

			$enc = encProcess::get_instance();
			$trade_info = sanitize_text_field( wp_unslash( $_REQUEST['TradeInfo'] ) );
			$trade_sha  = sanitize_text_field( wp_unslash( $_REQUEST['TradeSha'] ) );

			$local_sha = $enc->aes_sha256_str( $trade_info, $hash_key, $hash_iv );
			$sha_ok    = hash_equals( (string) $local_sha, (string) $trade_sha );

			if ( ! $sha_ok ) {
				if ( $this->logger ) {
					$this->logger->warning( 'PRG: TradeSha validation failed', array( 'order_id' => $order_id ) );
				}
				$order->update_status( 'failed', 'Payment failed: SHA_INVALID (SHA validate fail)' );
				$cart_url = esc_url( wc_get_cart_url() );
				$shop_url = esc_url( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) );
				$order->update_meta_data( '_newebpay_return_message', wp_kses(
					'請重新填單<br><br><div class="wc-block-order-confirmation-status__actions"><button type="button" onclick="window.location.href=\'' . esc_js( $cart_url ) . '\'" class="wc-block-order-confirmation-status__button wc-block-order-confirmation-status__button--try-again">返回購物車</button> <button type="button" onclick="window.location.href=\'' . esc_js( $shop_url ) . '\'" class="wc-block-order-confirmation-status__button wc-block-order-confirmation-status__button--my-account">返回商店</button></div>',
					array(
						'br'     => array(),
						'div'    => array( 'class' => true ),
						'button' => array( 'type' => true, 'onclick' => true, 'class' => true ),
					)
				) );
				$order->save();
				wp_safe_redirect( $order->get_checkout_order_received_url() );
				exit;
			}

			$req = $enc->create_aes_decrypt( $trade_info, $hash_key, $hash_iv );
			$req = is_array( $req ) ? $req : array();

			$status      = $req['Status'] ?? null;
			$message     = $req['Message'] ?? null;
			$paymenttype = $req['PaymentType'] ?? null;
			$tradeno     = $req['TradeNo'] ?? null;

			// 非即時取號成功（Status 可能是 CUSTOM）
			$is_vacc_success    = ( $paymenttype === 'VACC' && ! empty( $req['BankCode'] ) && ! empty( $req['CodeNo'] ) );
			$is_cvs_success     = ( $paymenttype === 'CVS' && ! empty( $req['CodeNo'] ) );
			$is_barcode_success = ( $paymenttype === 'BARCODE' && ( ! empty( $req['Barcode_1'] ) || ! empty( $req['Barcode_2'] ) || ! empty( $req['Barcode_3'] ) ) );

			$is_success = ( $status === 'SUCCESS' ) || $is_vacc_success || $is_cvs_success || $is_barcode_success;

			if ( $tradeno ) {
				$order->set_transaction_id( (string) $tradeno );
			}

			if ( ! $is_success ) {
				$note = sprintf( 'Payment failed: %s (%s)', (string) $status, (string) $message );
				$order->update_status( 'failed', $note );

				$cart_url = esc_url( wc_get_cart_url() );
				$shop_url = esc_url( wc_get_page_permalink( 'shop' ) ?: home_url( '/' ) );
				$error_html = '交易失敗，請重新填單<br>錯誤代碼：' . esc_html( (string) $status ) . '<br>錯誤訊息：' . esc_html( urldecode( (string) $message ) )
					. '<br><br><div class="wc-block-order-confirmation-status__actions"><button type="button" onclick="window.location.href=\'' . esc_js( $cart_url ) . '\'" class="wc-block-order-confirmation-status__button wc-block-order-confirmation-status__button--try-again">返回購物車</button> <button type="button" onclick="window.location.href=\'' . esc_js( $shop_url ) . '\'" class="wc-block-order-confirmation-status__button wc-block-order-confirmation-status__button--my-account">返回商店</button></div>';
				$order->update_meta_data( '_newebpay_return_message', wp_kses(
					$error_html,
					array(
						'br'     => array(),
						'div'    => array( 'class' => true ),
						'button' => array( 'type' => true, 'onclick' => true, 'class' => true ),
					)
				) );
				$order->save();
				wp_safe_redirect( $order->get_checkout_order_received_url() );
				exit;
			}

			// 成功：清除先前失敗訊息、轉為 processing（允許從 failed 轉回）
			$order->delete_meta_data( '_newebpay_return_message' );
			$order->save();
			$order->update_status( 'processing' );

			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}
	}

	$GLOBALS['wc_newebpay_payment'] = WC_Newebpay_Payment::get_instance();

}
