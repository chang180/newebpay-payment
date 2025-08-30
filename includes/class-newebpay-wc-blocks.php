<?php
/**
 * WooCommerce Blocks 整合
 * 支援 WooCommerce 區塊結帳系統
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_WooCommerce_Blocks_Integration {
    
    /**
     * 單例實例
     */
    private static $instance = null;
    
    /**
     * 取得單例實例
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 建構函式
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * 初始化
     */
    public function init() {
        // 檢查 WooCommerce Blocks 是否可用
        if ( ! $this->is_wc_blocks_available() ) {
            return;
        }
        
        // 註冊區塊支援
        add_action( 'woocommerce_blocks_loaded', array( $this, 'register_payment_method_blocks' ) );
        
        // 註冊腳本和樣式
        add_action( 'init', array( $this, 'register_block_scripts' ) );
        
        // 處理付款方式資料
        add_filter( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'process_payment_context' ), 10, 2 );
    }
    
    /**
     * 檢查 WooCommerce Blocks 是否可用
     */
    private function is_wc_blocks_available() {
        return class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' );
    }
    
    /**
     * 註冊付款方式區塊
     */
    public function register_payment_method_blocks() {
        if ( ! $this->is_wc_blocks_available() ) {
            return;
        }
        
        // 註冊 Newebpay 付款方式區塊
        $container = Automattic\WooCommerce\Blocks\Package::container();
        $container->register(
            Newebpay_Payment_Block::class,
            function( $container ) {
                return new Newebpay_Payment_Block();
            }
        );
        
        // 將付款方式加入區塊系統
        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                $payment_method_registry->register( new Newebpay_Payment_Block() );
            }
        );
    }
    
    /**
     * 註冊區塊專用腳本
     */
    public function register_block_scripts() {
        $script_path = NEWEB_MAIN_PATH . '/assets/js/wc-blocks-checkout.js';
        $script_url = plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'assets/js/wc-blocks-checkout.js';
        
        // 註冊結帳區塊腳本
        wp_register_script(
            'newebpay-blocks-checkout',
            $script_url,
            array( 'wc-blocks-checkout' ),
            '1.0.10',
            true
        );
        
        // 傳遞必要資料
        wp_localize_script( 'newebpay-blocks-checkout', 'newebpayBlocksData', array(
            'title' => __( 'Newebpay', 'newebpay-payment' ),
            'description' => __( '使用藍新金流進行安全付款', 'newebpay-payment' ),
            'logoUrl' => plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'assets/images/newebpay-logo.png',
            'supportsFeatures' => array(
                'products',
                'refunds'
            )
        ) );
        
        // 註冊樣式
        wp_register_style(
            'newebpay-blocks-checkout',
            plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'assets/css/wc-blocks-checkout.css',
            array(),
            '1.0.10'
        );
    }
    
    /**
     * 處理付款上下文
     */
    public function process_payment_context( $result, $context ) {
        // 確保 Newebpay 付款方式在區塊結帳中正常運作
        if ( isset( $context['payment_method'] ) && $context['payment_method'] === 'newebpay' ) {
            // 這裡可以加入額外的處理邏輯
            error_log( 'Newebpay WC Blocks: Processing payment with context' );
        }
        
        return $result;
    }
}

/**
 * Newebpay 付款方式區塊類別
 */
class Newebpay_Payment_Block extends Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
    
    /**
     * 付款方式名稱
     */
    protected $name = 'newebpay';
    
    /**
     * 初始化設定
     */
    public function initialize() {
        $this->settings = get_option( 'woocommerce_newebpay_settings', array() );
    }
    
    /**
     * 檢查是否啟用
     */
    public function is_active() {
        return ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'];
    }
    
    /**
     * 取得腳本控制代碼
     */
    public function get_payment_method_script_handles() {
        wp_enqueue_script( 'newebpay-blocks-checkout' );
        return array( 'newebpay-blocks-checkout' );
    }
    
    /**
     * 取得付款方式資料
     */
    public function get_payment_method_data() {
        return array(
            'title' => $this->get_newebpay_setting( 'title' ),
            'description' => $this->get_newebpay_setting( 'description' ),
            'supports' => $this->get_newebpay_supported_features(),
            'logo_url' => plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'assets/images/newebpay-logo.png'
        );
    }
    
    /**
     * 取得支援的功能
     */
    private function get_newebpay_supported_features() {
        $payment_gateways = WC()->payment_gateways()->payment_gateways();
        $newebpay_gateway = isset( $payment_gateways['newebpay'] ) ? $payment_gateways['newebpay'] : null;
        
        if ( $newebpay_gateway ) {
            return $newebpay_gateway->supports;
        }
        
        return array( 'products' );
    }
    
    /**
     * 取得設定值
     */
    private function get_newebpay_setting( $key, $default = '' ) {
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }
}

// 初始化區塊整合
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        Newebpay_WooCommerce_Blocks_Integration::get_instance();
    }
}, 15 );
?>
