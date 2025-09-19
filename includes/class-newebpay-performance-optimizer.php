<?php
/**
 * Newebpay 效能優化類別
 * 減少不必要的 Hook 註冊和優化資料庫查詢
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_Performance_Optimizer {
    
    /**
     * 單例實例
     */
    private static $instance = null;
    
    /**
     * 已註冊的 Hooks 快取
     */
    private $registered_hooks = array();
    
    /**
     * 資料庫查詢快取
     */
    private $query_cache = array();
    
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
        $this->init_optimizations();
    }
    
    /**
     * 初始化優化設定
     */
    private function init_optimizations() {
        // 只在必要時載入資源
        add_action( 'wp_enqueue_scripts', array( $this, 'conditional_script_loading' ) );
        
        // 優化資料庫查詢
        add_action( 'init', array( $this, 'setup_query_optimizations' ) );
        
        // 快取設定資料
        add_action( 'init', array( $this, 'cache_settings' ) );
    }
    
    /**
     * 條件式腳本載入
     */
    public function conditional_script_loading() {
        // 只在結帳頁面載入付款相關腳本
        if ( is_checkout() || is_wc_endpoint_url( 'order-pay' ) ) {
            $this->load_checkout_scripts();
        }
        
        // 只在管理後台載入管理腳本
        if ( is_admin() ) {
            $this->load_admin_scripts();
        }
    }
    
    /**
     * 載入結帳頁面腳本
     */
    private function load_checkout_scripts() {
        wp_enqueue_script(
            'newebpay-checkout',
            plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'assets/js/wc-blocks-checkout.js',
            array( 'jquery', 'wc-checkout' ),
            '1.0.10',
            true
        );
        
        wp_enqueue_style(
            'newebpay-checkout',
            plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'assets/css/wc-blocks-checkout.css',
            array(),
            '1.0.10'
        );
    }
    
    /**
     * 載入管理後台腳本
     */
    private function load_admin_scripts() {
        wp_enqueue_script(
            'newebpay-admin',
            plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'assets/js/admin/newebpayAdminAjax.js',
            array( 'jquery' ),
            '1.0.10',
            true
        );
    }
    
    /**
     * 設定查詢優化
     */
    public function setup_query_optimizations() {
        // 使用 WordPress 物件快取
        add_filter( 'woocommerce_get_order', array( $this, 'cache_order_queries' ), 10, 2 );
        
        // 優化設定查詢
        add_filter( 'option_woocommerce_newebpay_settings', array( $this, 'cache_settings_query' ) );
    }
    
    /**
     * 快取訂單查詢
     */
    public function cache_order_queries( $order, $order_id ) {
        if ( ! $order_id ) {
            return $order;
        }
        
        $cache_key = 'newebpay_order_' . $order_id;
        $cached_order = wp_cache_get( $cache_key, 'newebpay' );
        
        if ( false === $cached_order ) {
            wp_cache_set( $cache_key, $order, 'newebpay', 3600 );
        }
        
        return $order;
    }
    
    /**
     * 快取設定查詢
     */
    public function cache_settings_query( $settings ) {
        if ( false === $settings ) {
            $settings = get_option( 'woocommerce_newebpay_settings', array() );
            wp_cache_set( 'newebpay_settings', $settings, 'newebpay', 3600 );
        }
        
        return $settings;
    }
    
    /**
     * 快取設定資料
     */
    public function cache_settings() {
        $settings = get_option( 'woocommerce_newebpay_settings', array() );
        wp_cache_set( 'newebpay_settings', $settings, 'newebpay', 3600 );
    }
    
    /**
     * 取得快取的設定
     */
    public function get_cached_settings() {
        $settings = wp_cache_get( 'newebpay_settings', 'newebpay' );
        
        if ( false === $settings ) {
            $settings = get_option( 'woocommerce_newebpay_settings', array() );
            wp_cache_set( 'newebpay_settings', $settings, 'newebpay', 3600 );
        }
        
        return $settings;
    }
    
    /**
     * 優化資料庫查詢 - 批次取得訂單資料
     */
    public function batch_get_orders( $order_ids ) {
        if ( empty( $order_ids ) ) {
            return array();
        }
        
        $cache_key = 'newebpay_orders_' . md5( implode( ',', $order_ids ) );
        $cached_orders = wp_cache_get( $cache_key, 'newebpay' );
        
        if ( false !== $cached_orders ) {
            return $cached_orders;
        }
        
        $orders = wc_get_orders( array(
            'include' => $order_ids,
            'limit' => -1
        ) );
        
        wp_cache_set( $cache_key, $orders, 'newebpay', 1800 );
        
        return $orders;
    }
    
    /**
     * 優化用戶訂單查詢
     */
    public function get_user_orders_optimized( $user_id, $limit = 5 ) {
        $cache_key = 'newebpay_user_orders_' . $user_id . '_' . $limit;
        $cached_orders = wp_cache_get( $cache_key, 'newebpay' );
        
        if ( false !== $cached_orders ) {
            return $cached_orders;
        }
        
        $orders = wc_get_orders( array(
            'customer_id' => $user_id,
            'payment_method' => 'newebpay',
            'status' => array( 'processing', 'completed' ),
            'limit' => $limit,
            'orderby' => 'date',
            'order' => 'DESC'
        ) );
        
        wp_cache_set( $cache_key, $orders, 'newebpay', 900 );
        
        return $orders;
    }
    
    /**
     * 清理快取
     */
    public function clear_cache( $type = 'all' ) {
        switch ( $type ) {
            case 'settings':
                wp_cache_delete( 'newebpay_settings', 'newebpay' );
                break;
                
            case 'orders':
                wp_cache_flush_group( 'newebpay' );
                break;
                
            case 'all':
            default:
                wp_cache_flush_group( 'newebpay' );
                break;
        }
    }
    
    /**
     * 檢查是否應該載入特定功能
     */
    public function should_load_feature( $feature ) {
        switch ( $feature ) {
            case 'blocks_integration':
                return class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' );
                
            case 'admin_features':
                return is_admin() && current_user_can( 'manage_woocommerce' );
                
            case 'checkout_features':
                return is_checkout() || is_wc_endpoint_url( 'order-pay' );
                
            default:
                return true;
        }
    }
    
    /**
     * 延遲載入非關鍵功能
     */
    public function lazy_load_feature( $feature, $callback ) {
        if ( $this->should_load_feature( $feature ) ) {
            add_action( 'wp_loaded', $callback, 20 );
        }
    }
    
    /**
     * 優化 Hook 註冊
     */
    public function register_optimized_hook( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        $hook_key = $hook . '_' . $priority . '_' . $accepted_args;
        
        // 避免重複註冊
        if ( isset( $this->registered_hooks[ $hook_key ] ) ) {
            return;
        }
        
        add_action( $hook, $callback, $priority, $accepted_args );
        $this->registered_hooks[ $hook_key ] = true;
    }
    
    /**
     * 取得效能統計
     */
    public function get_performance_stats() {
        return array(
            'registered_hooks' => count( $this->registered_hooks ),
            'cache_hits' => wp_cache_get( 'newebpay_cache_hits', 'newebpay' ) ?: 0,
            'cache_misses' => wp_cache_get( 'newebpay_cache_misses', 'newebpay' ) ?: 0,
            'memory_usage' => memory_get_usage( true ),
            'peak_memory' => memory_get_peak_usage( true )
        );
    }
}
