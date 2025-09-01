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
        
        // 註冊區塊支援 - 正確的 Hook
        add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_payment_method_type' ) );
        
        // 註冊腳本和樣式
        add_action( 'init', array( $this, 'register_block_scripts' ) );
        
        // 註冊 REST API 端點
        add_action( 'rest_api_init', array( $this, 'register_rest_endpoints' ) );
        
        // 處理付款方式資料 - 確保參數正確傳遞給傳統閘道
        add_action( 'woocommerce_rest_checkout_process_payment_with_context', array( $this, 'process_payment_with_blocks_context' ), 10, 2 );
        
        // 在付款閘道處理前確保設置正確的資料
        add_action( 'woocommerce_gateway_process_payment', array( $this, 'ensure_payment_data' ), 1 );
        
        // 額外的掛鉤點確保資料傳遞
        add_action( 'woocommerce_checkout_order_processed', array( $this, 'ensure_post_data' ), 1, 3 );
        add_filter( 'woocommerce_checkout_posted_data', array( $this, 'modify_checkout_posted_data' ), 10, 1 );
        
        // 註冊 AJAX 處理器供前端使用
        add_action( 'wp_ajax_newebpay_get_payment_methods', array( $this, 'ajax_get_payment_methods' ) );
        add_action( 'wp_ajax_nopriv_newebpay_get_payment_methods', array( $this, 'ajax_get_payment_methods' ) );
    }
    
    /**
     * 檢查 WooCommerce Blocks 是否可用
     */
    private function is_wc_blocks_available() {
        return class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' );
    }
    
    /**
     * 註冊付款方式類型 (WooCommerce Blocks 正確方法)
     */
    public function register_payment_method_type( $payment_method_registry ) {
        if ( ! $this->is_wc_blocks_available() ) {
            return;
        }
        
        // 註冊 Newebpay 付款方式到 WooCommerce Blocks
        $payment_method_registry->register( new Newebpay_Payment_Block() );
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
            'apiUrl' => rest_url( 'newebpay/v1' ),
            'nonce' => wp_create_nonce( 'newebpay_blocks_nonce' ),
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
     * 註冊 REST API 端點
     */
    public function register_rest_endpoints() {
        // 註冊付款方式查詢端點
        register_rest_route( 'newebpay/v1', '/payment-methods', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_get_payment_methods' ),
            'permission_callback' => '__return_true'
        ) );

        // 管理員用：比較不同來源的付款方式（設定、WC_newebpay 內部解析、Blocks 產出）
        register_rest_route( 'newebpay/v1', '/admin/compare-payment-methods', array(
            'methods' => 'GET',
            'callback' => array( $this, 'rest_admin_compare_payment_methods' ),
            'permission_callback' => function() {
                return current_user_can( 'manage_options' );
            }
        ) );
    }

    /**
     * 管理員用 REST: 比較設定來源、WC_newebpay 的選擇結果（透過 reflection 嘗試）以及 Blocks 回傳
     */
    public function rest_admin_compare_payment_methods( $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'forbidden' ), 403 );
        }

        // 來源 A: 直接從設定解析
        $nwp_settings = get_option( 'woocommerce_newebpay_settings' );
        $selected_from_settings = array();
        if ( is_array( $nwp_settings ) ) {
            foreach ( $nwp_settings as $key => $value ) {
                if ( strpos( $key, 'NwpPaymentMethod' ) !== false && $value === 'yes' ) {
                    $selected_from_settings[ str_replace( 'NwpPaymentMethod', '', $key ) ] = 1;
                }
            }
        }

        // 來源 B: 嘗試使用 WC_newebpay 的內部解析 (private method) via Reflection
        $selected_from_wc = array();
        if ( class_exists( 'WC_newebpay' ) ) {
            try {
                $wc = new WC_newebpay();
                if ( method_exists( $wc, 'get_selected_payment' ) || ( new ReflectionClass( $wc ) )->hasMethod( 'get_selected_payment' ) ) {
                    $ref = new ReflectionMethod( $wc, 'get_selected_payment' );
                    $ref->setAccessible( true );
                    $selected_from_wc = (array) $ref->invoke( $wc );
                }
            } catch ( Exception $e ) {
                // reflection 失敗時退回空陣列
                $selected_from_wc = array();
            }
        }

        // 來源 C: Blocks REST 產出
        $blocks_methods = $this->get_payment_methods_for_rest();
        $blocks_ids = array();
        foreach ( $blocks_methods as $m ) {
            if ( isset( $m['id'] ) ) {
                $blocks_ids[] = $m['id'];
            }
        }

        // 計算差異
        $settings_keys = array_values( array_map( 'strval', array_keys( $selected_from_settings ) ) );
        $wc_keys = array_values( array_map( 'strval', array_keys( $selected_from_wc ) ) );

        $missing_in_blocks = array_values( array_diff( $settings_keys, $blocks_ids ) );
        $missing_in_settings = array_values( array_diff( $blocks_ids, $settings_keys ) );
        $missing_in_wc = array_values( array_diff( $settings_keys, $wc_keys ) );

        return new WP_REST_Response( array(
            'success' => true,
            'source_settings' => array_values( $settings_keys ),
            'source_wc' => array_values( $wc_keys ),
            'source_blocks' => $blocks_ids,
            'differences' => array(
                'missing_in_blocks' => $missing_in_blocks,
                'missing_in_settings' => $missing_in_settings,
                'missing_in_wc' => $missing_in_wc,
            ),
        ), 200 );
    }
    
    /**
     * REST API: 取得付款方式
     */
    public function rest_get_payment_methods( $request ) {
        $methods = $this->get_payment_methods_for_rest();

        // 讀取是否支援 CVSCOMNotPayed（超商取貨不付款）
        $nwp_settings = get_option( 'woocommerce_newebpay_settings' );
        $cvscom_not_payed = false;
        if ( is_array( $nwp_settings ) && isset( $nwp_settings['NwpPaymentMethodCVSCOMNotPayed'] ) && $nwp_settings['NwpPaymentMethodCVSCOMNotPayed'] === 'yes' ) {
            $cvscom_not_payed = true;
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data' => $methods,
            'count' => count( $methods ),
            'cvscom_not_payed' => $cvscom_not_payed,
            'source' => 'rest_api',
            'version' => '1.0.10'
        ), 200 );
    }
    
    /**
     * 為 REST API 取得付款方式
     */
    private function get_payment_methods_for_rest() {
        // 優先使用傳統 WC_newebpay 的 get_selected_payment() 結果，確保與 order-pay 行為一致
        if ( class_exists( 'WC_newebpay' ) ) {
            // 不能呼叫 WC_newebpay::get_selected_payment()（private），改用同樣的解析設定邏輯
            $nwp_settings = get_option( 'woocommerce_newebpay_settings' );
            if ( ! $nwp_settings || ! is_array( $nwp_settings ) ) {
                return array();
            }

            $selected = array();
            foreach ( $nwp_settings as $row => $value ) {
                if ( strpos( $row, 'NwpPaymentMethod' ) !== false && $value === 'yes' ) {
                    $method_key = str_replace( 'NwpPaymentMethod', '', $row );
                    $selected[ $method_key ] = 1;
                }
            }

            if ( empty( $selected ) ) {
                return array();
            }

            // 顯示名稱對應（參考 nwpMPG::convert_payment）
            $names = array(
                'Credit'     => '信用卡一次付清',
                'AndroidPay' => 'Google Pay',
                'SamsungPay' => 'Samsung Pay',
                'LinePay'    => 'Line Pay',
                'Inst'       => '信用卡分期',
                'CreditRed'  => '信用卡紅利',
                'UnionPay'   => '銀聯卡',
                'Webatm'     => 'WEBATM',
                'Vacc'       => 'ATM轉帳',
                'CVS'        => '超商代碼',
                'BARCODE'    => '超商條碼',
                'EsunWallet' => '玉山 Wallet',
                'TaiwanPay'  => '台灣 Pay',
                'BitoPay'    => 'BitoPay',
                'EZPWECHAT'  => '微信支付',
                'EZPALIPAY'  => '支付寶',
                'APPLEPAY'   => 'Apple Pay',
                'SmartPay'   => '智慧ATM2.0',
                'TWQR'       => 'TWQR',
                'CVSCOMPayed' => '超商取貨付款',
                'CVSCOMNotPayed' => '超商取貨不付款',
            );

            $methods = array();
            // 前端 ID 映射（保持向後相容）
            $frontend_map = array(
                'Credit' => 'credit',
                'AndroidPay' => 'googlepay',
                'SamsungPay' => 'samsungpay',
                'LinePay' => 'linepay',
                'Inst' => 'installment',
                'CreditRed' => 'creditred',
                'UnionPay' => 'unionpay',
                'Webatm' => 'webatm',
                'Vacc' => 'vacc',
                'CVS' => 'cvs',
                'BARCODE' => 'barcode',
                'EsunWallet' => 'esunwallet',
                'TaiwanPay' => 'taiwanpay',
                'BitoPay' => 'bitopay',
                'EZPWECHAT' => 'wechat',
                'EZPALIPAY' => 'alipay',
                'APPLEPAY' => 'applepay',
                'SmartPay' => 'smartpay',
                'TWQR' => 'twqr',
                'CVSCOMPayed' => 'cvscom',
                'CVSCOMNotPayed' => 'cvscom_not_payed',
            );

            foreach ( $selected as $method_key => $v ) {
                // 跳過不付款選項
                if ( $method_key === 'CVSCOMNotPayed' ) {
                    continue;
                }

                $frontend_id = isset( $frontend_map[ $method_key ] ) ? $frontend_map[ $method_key ] : strtolower( $method_key );

                $methods[] = array(
                    'name' => isset( $names[ $method_key ] ) ? $names[ $method_key ] : $method_key,
                    'description' => '',
                    'id' => $method_key,
                    'frontend_id' => $frontend_id,
                    'enabled' => true,
                    'setting_key' => 'NwpPaymentMethod' . $method_key,
                );
            }

            return $methods;
        }

        return array();
    }
    
    /**
     * 取得所有付款方式定義 (REST API 版本)
     */
    private function get_all_payment_methods_for_rest() {
        return array(
            'credit' => array(
                'name' => __( '信用卡', 'newebpay-payment' ),
                'description' => __( '支援各大銀行信用卡', 'newebpay-payment' )
            ),
            'webatm' => array(
                'name' => __( '網路ATM', 'newebpay-payment' ),
                'description' => __( '使用讀卡機進行轉帳', 'newebpay-payment' )
            ),
            'vacc' => array(
                'name' => __( 'ATM轉帳', 'newebpay-payment' ),
                'description' => __( '虛擬帳號ATM轉帳', 'newebpay-payment' )
            ),
            'cvs' => array(
                'name' => __( '超商代碼', 'newebpay-payment' ),
                'description' => __( '超商代碼繳費', 'newebpay-payment' )
            ),
            'barcode' => array(
                'name' => __( '超商條碼', 'newebpay-payment' ),
                'description' => __( '超商條碼繳費', 'newebpay-payment' )
            ),
            'smartpay' => array(
                'name' => __( '智慧ATM2.0', 'newebpay-payment' ),
                'description' => __( '智慧型ATM轉帳', 'newebpay-payment' )
            ),
            'cvscom' => array(
                'name' => __( '超商取貨付款', 'newebpay-payment' ),
                'description' => __( '超商取貨付款', 'newebpay-payment' )
            )
        );
    }
    
    /**
     * 取得付款方式的設定鍵值 (REST API 版本)
     */
    private function get_payment_method_setting_key_for_rest( $method_key ) {
        $setting_keys = array(
            'credit' => 'NwpPaymentMethodCredit',
            'webatm' => 'NwpPaymentMethodWebatm', 
            'vacc' => 'NwpPaymentMethodVacc',
            'cvs' => 'NwpPaymentMethodCVS',
            'barcode' => 'NwpPaymentMethodBARCODE',
            'smartpay' => 'NwpPaymentMethodSmartPay',
            'cvscom' => 'NwpPaymentMethodCVSCOMPayed'
        );
        
        return isset( $setting_keys[ $method_key ] ) ? $setting_keys[ $method_key ] : 'NwpPaymentMethod' . ucfirst( $method_key );
    }
    
    /**
     * 處理 WooCommerce Blocks 付款上下文
     */
    public function process_payment_with_blocks_context( $context, $result ) {
        // 確保這是 Newebpay 付款方式
        if ( ! isset( $context->payment_data['payment_method'] ) || $context->payment_data['payment_method'] !== 'newebpay' ) {
            return;
        }
        
        // 從 JavaScript 傳來的資料中提取付款方式選擇
        $request_data = $context->payment_data;
        $payment_method_data = isset( $request_data['payment_method_data'] ) ? $request_data['payment_method_data'] : array();
        
        // 重要：確保設置了付款方式 ID（這是關鍵！）
        $_POST['payment_method'] = 'newebpay';
        
        // 設置 $_POST 參數以便傳統閘道能正確處理
        if ( isset( $payment_method_data['newebpay_selected_method'] ) ) {
            $selected_method = sanitize_text_field( $payment_method_data['newebpay_selected_method'] );
            
            // 智慧ATM2.0 特殊處理：將 smartpay 轉換為 SmartPay
            if ( $selected_method === 'smartpay' ) {
                $selected_method = 'SmartPay';
            }
            
            $_POST['nwp_selected_payments'] = $selected_method;
            $_POST['newebpay_selected_method'] = $selected_method;
            $_POST['selectedmethod'] = $selected_method;
        }
        
        // 檢查是否有從 window.newebpayData 傳來的資料
        if ( isset( $payment_method_data['selectedMethod'] ) ) {
            $selected_method = sanitize_text_field( $payment_method_data['selectedMethod'] );
            
            // 智慧ATM2.0 特殊處理：將 smartpay 轉換為 SmartPay
            if ( $selected_method === 'smartpay' ) {
                $selected_method = 'SmartPay';
            }
            
            $_POST['nwp_selected_payments'] = $selected_method;
            $_POST['newebpay_selected_method'] = $selected_method;
            $_POST['selectedmethod'] = $selected_method;
        }
        
        // 處理便利商店取貨付款選項
        if ( isset( $payment_method_data['cvscom_not_payed'] ) && $payment_method_data['cvscom_not_payed'] ) {
            $_POST['cvscom_not_payed'] = 'CVSCOMNotPayed';
        } else {
            $_POST['cvscom_not_payed'] = '';
        }
        
        // 如果沒有明確的付款方式選擇，設置預設值
        if ( empty( $_POST['selectedmethod'] ) && empty( $_POST['nwp_selected_payments'] ) ) {
            // 預設使用信用卡
            $_POST['selectedmethod'] = 'credit';
            $_POST['nwp_selected_payments'] = 'credit';
            $_POST['newebpay_selected_method'] = 'credit';
            
            if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log('★ 沒有明確選擇，使用預設: credit');
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('★ 最終設置的 $_POST 變量: selectedmethod=' . ($_POST['selectedmethod'] ?? '[未設置]') . ', nwp_selected_payments=' . ($_POST['nwp_selected_payments'] ?? '[未設置]'));
        }
    }
    
    /**
     * AJAX 處理器：取得付款方式
     */
    public function ajax_get_payment_methods() {
        // 驗證 nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'newebpay_blocks_nonce' ) ) {
            wp_die( json_encode( array(
                'success' => false,
                'message' => 'Nonce 驗證失敗',
                'error' => 'invalid_nonce'
            ) ) );
        }
        
        // 取得付款方式資料 - 直接調用相同的邏輯
        $methods = $this->get_payment_methods_for_ajax();
        
        wp_die( json_encode( array(
            'success' => true,
            'data' => $methods,
            'count' => count( $methods ),
            'source' => 'ajax',
            'version' => '1.0.10'
        ) ) );
    }
    
    /**
     * 為 AJAX 取得付款方式 (重用 Newebpay_Blocks 的邏輯)
     */
    private function get_payment_methods_for_ajax() {
        // 取得 Newebpay 設定
        $nwp_settings = get_option( 'woocommerce_newebpay_settings' );
        
        if ( ! $nwp_settings || ! is_array( $nwp_settings ) ) {
            return array();
        }
        
        // 檢查基本設定
        $required_settings = array( 'MerchantID', 'HashKey', 'HashIV' );
        foreach ( $required_settings as $setting ) {
            if ( empty( $nwp_settings[ $setting ] ) ) {
                return array();
            }
        }
        
        $methods = array();
        $all_methods = $this->get_all_payment_methods_for_ajax();
        
        foreach ( $all_methods as $key => $method ) {
            // 檢查該付款方式是否啟用
            $setting_key = $this->get_payment_method_setting_key_for_ajax( $key );
            if ( isset( $nwp_settings[ $setting_key ] ) && $nwp_settings[ $setting_key ] === 'yes' ) {
                $methods[ $key ] = array_merge( $method, array(
                    'id' => $key,
                    'enabled' => true,
                    'setting_key' => $setting_key
                ) );
            }
        }
        
        // 轉換關聯陣列為數字索引陣列
        return array_values( $methods );
    }
    
    /**
     * 取得所有付款方式定義 (AJAX 版本)
     */
    private function get_all_payment_methods_for_ajax() {
        return array(
            'credit' => array(
                'name' => __( '信用卡', 'newebpay-payment' ),
                'description' => __( '支援各大銀行信用卡', 'newebpay-payment' )
            ),
            'webatm' => array(
                'name' => __( '網路ATM', 'newebpay-payment' ),
                'description' => __( '使用讀卡機進行轉帳', 'newebpay-payment' )
            ),
            'vacc' => array(
                'name' => __( 'ATM轉帳', 'newebpay-payment' ),
                'description' => __( '虛擬帳號ATM轉帳', 'newebpay-payment' )
            ),
            'cvs' => array(
                'name' => __( '超商代碼', 'newebpay-payment' ),
                'description' => __( '超商代碼繳費', 'newebpay-payment' )
            ),
            'barcode' => array(
                'name' => __( '超商條碼', 'newebpay-payment' ),
                'description' => __( '超商條碼繳費', 'newebpay-payment' )
            ),
            'smartpay' => array(
                'name' => __( '智慧ATM2.0', 'newebpay-payment' ),
                'description' => __( '智慧型ATM轉帳', 'newebpay-payment' )
            ),
            'cvscom' => array(
                'name' => __( '超商取貨付款', 'newebpay-payment' ),
                'description' => __( '超商取貨付款', 'newebpay-payment' )
            )
        );
    }
    
    /**
     * 取得付款方式的設定鍵值 (AJAX 版本)
     */
    private function get_payment_method_setting_key_for_ajax( $method_key ) {
        $setting_keys = array(
            'credit' => 'NwpPaymentMethodCredit',
            'webatm' => 'NwpPaymentMethodWebatm', 
            'vacc' => 'NwpPaymentMethodVacc',
            'cvs' => 'NwpPaymentMethodCVS',
            'barcode' => 'NwpPaymentMethodBARCODE',
            'smartpay' => 'NwpPaymentMethodSmartPay',
            'cvscom' => 'NwpPaymentMethodCVSCOMPayed'
        );
        
        return isset( $setting_keys[ $method_key ] ) ? $setting_keys[ $method_key ] : 'NwpPaymentMethod' . ucfirst( $method_key );
    }
    
    /**
     * 修改結帳提交的資料
     */
    public function modify_checkout_posted_data( $data ) {
        // 僅處理 newebpay 付款
        if ( ! isset( $data['payment_method'] ) || $data['payment_method'] !== 'newebpay' ) {
            return $data;
        }
        
        // 確保包含付款方式選擇
        if ( ! isset( $data['selectedmethod'] ) || empty( $data['selectedmethod'] ) ) {
            $data['selectedmethod'] = 'credit';
            $data['nwp_selected_payments'] = 'credit';
            $data['newebpay_selected_method'] = 'credit';
        }
        
        if ( ! isset( $data['cvscom_not_payed'] ) ) {
            $data['cvscom_not_payed'] = '';
        }
        
        return $data;
    }
    
    /**
     * 確保 POST 資料在訂單處理時正確設置
     */
    public function ensure_post_data( $order_id, $posted_data, $order ) {
        // 僅處理 newebpay 付款
        if ( ! isset( $posted_data['payment_method'] ) || $posted_data['payment_method'] !== 'newebpay' ) {
            return;
        }
        
        // 確保 $_POST 包含必要的資料
        $_POST['payment_method'] = 'newebpay';
        
        if ( ! isset( $_POST['selectedmethod'] ) || empty( $_POST['selectedmethod'] ) ) {
            $_POST['selectedmethod'] = 'credit';
            $_POST['nwp_selected_payments'] = 'credit';
            $_POST['newebpay_selected_method'] = 'credit';
        }
        
        if ( ! isset( $_POST['cvscom_not_payed'] ) ) {
            $_POST['cvscom_not_payed'] = '';
        }
    }
    
    /**
     * 確保付款資料在閘道處理前正確設置
     */
    public function ensure_payment_data( $gateway_id ) {
        if ( $gateway_id !== 'newebpay' ) {
            return;
        }
        
        // 如果 payment_method 沒有設置，強制設置
        if ( ! isset( $_POST['payment_method'] ) ) {
            $_POST['payment_method'] = 'newebpay';
        }
        
        // 確保有基本的付款方式資料
        if ( ! isset( $_POST['selectedmethod'] ) && ! isset( $_POST['newebpay_selected_method'] ) ) {
            $_POST['selectedmethod'] = 'credit';
            $_POST['newebpay_selected_method'] = 'credit';
            $_POST['nwp_selected_payments'] = 'credit';
        }
    }
    
    /**
     * 處理付款上下文 (舊版，已停用)
     */
    public function process_payment_context( $context, $result ) {
        // 暫時註釋以避免 PaymentResult 物件錯誤
        // 這個方法目前不需要，因為實際付款處理在傳統閘道中進行
        
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
