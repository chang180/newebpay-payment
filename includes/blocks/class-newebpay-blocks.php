<?php
/**
 * Newebpay Blocks 管理類別
 * 
 * @package NewebpayPayment
 * @subpackage Blocks
 * @since 1.0.10
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_Blocks {
    
    /**
     * 單例實例
     */
    private static $instance = null;
    
    /**
     * 區塊列表
     */
    private $blocks = array();
    
    /**
     * 區塊目錄路徑
     */
    private $blocks_path;
    
    /**
     * 區塊 URL 路徑
     */
    private $blocks_url;
    
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
        $this->blocks_path = NEWEB_MAIN_PATH . '/includes/blocks';
        $this->blocks_url = plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'includes/blocks';
        
        $this->init_hooks();
        $this->register_blocks();
        $this->load_admin();
    }
    
    /**
     * 初始化 Hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'register_block_types' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_block_assets' ) );
        add_filter( 'block_categories_all', array( $this, 'add_block_category' ), 10, 2 );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }
    
    /**
     * 註冊區塊列表
     */
    private function register_blocks() {
        $this->blocks = array(
            'payment-methods' => array(
                'name' => 'newebpay/payment-methods',
                'title' => __( 'Newebpay 付款方式', 'newebpay-payment' ),
                'description' => __( '顯示 Newebpay 支援的付款方式選擇', 'newebpay-payment' ),
                'icon' => 'money-alt',
                'category' => 'newebpay',
                'render_callback' => array( $this, 'render_payment_methods_block' ),
                'attributes' => array(
                    'showMethods' => array(
                        'type' => 'array',
                        'default' => array()
                    ),
                    'layout' => array(
                        'type' => 'string',
                        'default' => 'grid'
                    ),
                    'showDescriptions' => array(
                        'type' => 'boolean',
                        'default' => true
                    )
                )
            )
        );
    }
    
    /**
     * 註冊區塊類型
     */
    public function register_block_types() {
        // 檢查 WooCommerce 是否啟用
        if ( ! class_exists( 'WooCommerce' ) ) {
            return;
        }
        
        foreach ( $this->blocks as $block_key => $block_config ) {
            // 使用新的 block.json 註冊方式
            $block_json_path = $this->blocks_path . '/blocks/' . $block_key;
            
            if ( file_exists( $block_json_path . '/block.json' ) ) {
                register_block_type( $block_json_path, array(
                    'render_callback' => $block_config['render_callback']
                ) );
            } else {
                // 降級為手動註冊
                register_block_type( $block_config['name'], array(
                    'attributes' => $block_config['attributes'],
                    'render_callback' => $block_config['render_callback'],
                    'editor_script' => 'newebpay-blocks-editor',
                    'editor_style' => 'newebpay-blocks-editor',
                    'style' => 'newebpay-blocks-frontend'
                ) );
            }
        }
        
        // 記錄成功註冊的訊息
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'Newebpay Blocks: Successfully registered ' . count( $this->blocks ) . ' block types.' );
        }
    }
    
    /**
     * 載入編輯器資源
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'newebpay-blocks-editor',
            $this->blocks_url . '/assets/js/blocks-editor.js',
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components' ),
            '1.0.10',
            true
        );
        
        wp_enqueue_style(
            'newebpay-blocks-editor',
            $this->blocks_url . '/assets/css/blocks-editor.css',
            array( 'wp-edit-blocks' ),
            '1.0.10'
        );
        
        // 傳遞資料給 JavaScript
        wp_localize_script( 'newebpay-blocks-editor', 'newebpayBlocks', array(
            'nonce' => wp_create_nonce( 'newebpay_blocks_nonce' ),
            'apiUrl' => rest_url( 'newebpay/v1/' ),
            'blocks' => $this->blocks,
            'availableMethods' => $this->get_available_payment_methods()
        ) );
    }
    
    /**
     * 載入前端資源
     */
    public function enqueue_block_assets() {
        // 只在有使用區塊的頁面載入資源
        if ( $this->has_newebpay_blocks() ) {
            wp_enqueue_style(
                'newebpay-blocks-frontend',
                $this->blocks_url . '/assets/css/blocks-frontend.css',
                array(),
                '1.0.10'
            );
            
            wp_enqueue_script(
                'newebpay-blocks-frontend',
                $this->blocks_url . '/assets/js/blocks-frontend.js',
                array( 'jquery' ),
                '1.0.10',
                true
            );
        }
    }
    
    /**
     * 新增區塊分類
     */
    public function add_block_category( $categories, $post ) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug' => 'newebpay',
                    'title' => __( 'Newebpay Payment', 'newebpay-payment' ),
                    'icon' => 'money-alt',
                ),
            )
        );
    }
    
    /**
     * 註冊 REST API 路由
     */
    public function register_rest_routes() {
        register_rest_route( 'newebpay/v1', '/payment-methods', array(
            'methods' => 'GET',
            'callback' => array( $this, 'api_get_payment_methods' ),
            'permission_callback' => array( $this, 'check_api_permissions' )
        ) );
        
        register_rest_route( 'newebpay/v1', '/status', array(
            'methods' => 'GET',
            'callback' => array( $this, 'api_get_status' ),
            'permission_callback' => array( $this, 'check_api_permissions' )
        ) );
    }
    
    /**
     * API: 取得付款方式
     */
    public function api_get_payment_methods( $request ) {
        $methods = $this->get_available_payment_methods();
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $methods,
            'count' => count( $methods ),
            'version' => '1.0.10'
        ), 200 );
    }
    
    /**
     * API: 取得狀態
     */
    public function api_get_status( $request ) {
        return new WP_REST_Response( array(
            'success' => true,
            'plugin_version' => '1.0.10',
            'blocks_version' => '1.0.10',
            'wordpress_version' => get_bloginfo( 'version' ),
            'woocommerce_active' => class_exists( 'WooCommerce' ),
            'blocks_registered' => count( $this->blocks ),
            'version' => '1.0.10'
        ), 200 );
    }
    
    /**
     * 檢查 API 權限
     */
    public function check_api_permissions( $request ) {
        return true; // 公開 API，或根據需求調整
    }
    
    /**
     * 渲染付款方式區塊
     */
    public function render_payment_methods_block( $attributes, $content ) {
        // 驗證屬性
        $attributes = $this->validate_payment_methods_attributes( $attributes );
        
        // 取得可用的付款方式
        $available_methods = $this->get_available_payment_methods();
        
        if ( empty( $available_methods ) ) {
            return '<p>' . __( '目前沒有可用的付款方式。', 'newebpay-payment' ) . '</p>';
        }
        
        // 篩選要顯示的付款方式
        $show_methods = $attributes['showMethods'];
        if ( ! empty( $show_methods ) ) {
            $available_methods = array_intersect_key( $available_methods, array_flip( $show_methods ) );
        }
        
        // 生成 HTML
        return $this->generate_payment_methods_html( $available_methods, $attributes );
    }
    
    /**
     * 驗證付款方式區塊屬性
     */
    private function validate_payment_methods_attributes( $attributes ) {
        $validated = array();
        
        // 驗證 showMethods
        if ( isset( $attributes['showMethods'] ) && is_array( $attributes['showMethods'] ) ) {
            $allowed_methods = array_keys( $this->get_all_payment_methods() );
            $validated['showMethods'] = array_intersect( $attributes['showMethods'], $allowed_methods );
        } else {
            $validated['showMethods'] = array();
        }
        
        // 驗證 layout
        $allowed_layouts = array( 'grid', 'list', 'inline' );
        $validated['layout'] = isset( $attributes['layout'] ) && in_array( $attributes['layout'], $allowed_layouts ) 
            ? $attributes['layout'] 
            : 'grid';
        
        // 驗證 showDescriptions
        $validated['showDescriptions'] = isset( $attributes['showDescriptions'] ) 
            ? (bool) $attributes['showDescriptions'] 
            : true;
        
        return $validated;
    }
    
    /**
     * 取得可用的付款方式
     */
    private function get_available_payment_methods() {
        // 取得 Newebpay 設定
        $nwp_settings = get_option( 'woocommerce_nwp_settings' );
        
        if ( ! $nwp_settings || ! is_array( $nwp_settings ) ) {
            return array();
        }
        
        $methods = array();
        $all_methods = $this->get_all_payment_methods();
        
        foreach ( $all_methods as $key => $method ) {
            if ( isset( $nwp_settings[ $key ] ) && $nwp_settings[ $key ] === 'yes' ) {
                $methods[ $key ] = $method;
            }
        }
        
        return $methods;
    }
    
    /**
     * 取得所有付款方式定義
     */
    private function get_all_payment_methods() {
        return array(
            'credit' => array(
                'name' => __( '信用卡', 'newebpay-payment' ),
                'description' => __( '支援各大銀行信用卡', 'newebpay-payment' ),
                'icon' => 'credit-card'
            ),
            'webatm' => array(
                'name' => __( '網路ATM', 'newebpay-payment' ),
                'description' => __( '使用讀卡機進行轉帳', 'newebpay-payment' ),
                'icon' => 'atm'
            ),
            'vacc' => array(
                'name' => __( 'ATM轉帳', 'newebpay-payment' ),
                'description' => __( '虛擬帳號ATM轉帳', 'newebpay-payment' ),
                'icon' => 'bank'
            ),
            'cvs' => array(
                'name' => __( '超商代碼', 'newebpay-payment' ),
                'description' => __( '超商代碼繳費', 'newebpay-payment' ),
                'icon' => 'store'
            ),
            'barcode' => array(
                'name' => __( '超商條碼', 'newebpay-payment' ),
                'description' => __( '超商條碼繳費', 'newebpay-payment' ),
                'icon' => 'barcode'
            ),
            'SmartPay' => array(
                'name' => __( '智慧ATM2.0', 'newebpay-payment' ),
                'description' => __( '智慧型ATM轉帳', 'newebpay-payment' ),
                'icon' => 'smartphone'
            )
        );
    }
    
    /**
     * 生成付款方式 HTML
     */
    private function generate_payment_methods_html( $methods, $attributes ) {
        $layout = $attributes['layout'];
        $show_descriptions = $attributes['showDescriptions'];
        
        $html = '<div class="wp-block-newebpay-payment-methods is-layout-' . esc_attr( $layout ) . '">';
        
        foreach ( $methods as $key => $method ) {
            $html .= '<div class="newebpay-payment-method" data-method="' . esc_attr( $key ) . '">';
            $html .= '<div class="method-icon">';
            $html .= '<span class="dashicons dashicons-' . esc_attr( $method['icon'] ) . '"></span>';
            $html .= '</div>';
            $html .= '<div class="method-content">';
            $html .= '<h4 class="method-name">' . esc_html( $method['name'] ) . '</h4>';
            
            if ( $show_descriptions && ! empty( $method['description'] ) ) {
                $html .= '<p class="method-description">' . esc_html( $method['description'] ) . '</p>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * 檢查頁面是否使用了 Newebpay 區塊
     */
    private function has_newebpay_blocks() {
        global $post;
        
        if ( ! $post || ! has_blocks( $post->post_content ) ) {
            return false;
        }
        
        foreach ( $this->blocks as $block_config ) {
            if ( has_block( $block_config['name'], $post ) ) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 載入管理功能
     */
    private function load_admin() {
        if ( is_admin() ) {
            include_once $this->blocks_path . '/class-newebpay-blocks-admin.php';
        }
    }
}