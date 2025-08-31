<?php
/**
 * Newebpay Blocks Management
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
     * 資源檔案 URL 路徑
     */
    private $assets_url;
    
    /**
     * 外掛程式 URL
     */
    private $plugin_url;
    
    /**
     * 外掛程式路徑
     */
    private $plugin_path;
    
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
     * 將後端鍵值轉換為顯示標籤（參考 nwpMPG::convert_payment）
     */
    private function convert_backend_key_to_label( $payment_method ) {
        $method = array(
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

        return isset( $method[ $payment_method ] ) ? $method[ $payment_method ] : $payment_method;
    }
    
    /**
     * 建構函式
     */
    private function __construct() {
        $this->plugin_path = NEWEB_MAIN_PATH;
        $this->plugin_url = plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' );
        $this->blocks_path = NEWEB_MAIN_PATH . '/includes/blocks';
        $this->blocks_url = plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'includes/blocks';
        $this->assets_url = plugin_dir_url( NEWEB_MAIN_PATH . '/Central.php' ) . 'assets';
        
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
        
        // 添加測試短碼
        add_shortcode( 'newebpay_test_block', array( $this, 'render_test_shortcode' ) );
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
        // 檢查 Gutenberg 支援
        if ( ! function_exists( 'register_block_type' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Removed debug: Newebpay Blocks: register_block_type function not available. Gutenberg not supported.
                // error_log( 'Newebpay Blocks: register_block_type function not available. Gutenberg not supported.' );
            }
            return;
        }
        
        // 檢查 WooCommerce 是否啟用 - 改為更寬鬆的檢查，允許在沒有 WooCommerce 時也註冊區塊
        if ( ! function_exists( 'WC' ) && ! class_exists( 'WooCommerce' ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Removed debug: Newebpay Blocks: WooCommerce not detected. Blocks will still be registered for compatibility.
                // error_log( 'Newebpay Blocks: WooCommerce not detected. Blocks will still be registered for compatibility.' );
            }
        }
        
        $registered_count = 0;
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Removed debug: Newebpay Blocks: Starting block registration. Total blocks to register: {count}
            // error_log( 'Newebpay Blocks: Starting block registration. Total blocks to register: ' . count( $this->blocks ) );
        }
        
        foreach ( $this->blocks as $block_key => $block_config ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Removed debug: Newebpay Blocks: Attempting to register block '{$block_key}' with name '{$block_config['name']}'
                // error_log( "Newebpay Blocks: Attempting to register block '{$block_key}' with name '{$block_config['name']}'" );
            }
            
            // 使用新的 block.json 註冊方式
            $block_json_path = $this->blocks_path . '/blocks/' . $block_key;
            
            if ( file_exists( $block_json_path . '/block.json' ) ) {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    // Removed debug: Newebpay Blocks: Found block.json at {$block_json_path}/block.json
                    // error_log( "Newebpay Blocks: Found block.json at {$block_json_path}/block.json" );
                }
                
                $result = register_block_type( $block_json_path, array(
                    'render_callback' => $block_config['render_callback']
                ) );
                
                if ( $result ) {
                    $registered_count++;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        // Removed debug: Newebpay Blocks: Successfully registered block '{$block_config['name']}' from block.json
                        // error_log( "Newebpay Blocks: Successfully registered block '{$block_config['name']}' from block.json" );
                    }
                } else {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        // Removed debug: Newebpay Blocks: Failed to register block '{$block_config['name']}' from block.json
                        // error_log( "Newebpay Blocks: Failed to register block '{$block_config['name']}' from block.json" );
                    }
                }
            } else {
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    // Removed debug: Newebpay Blocks: block.json not found at {$block_json_path}, attempting manual registration
                    // error_log( "Newebpay Blocks: block.json not found at {$block_json_path}, attempting manual registration" );
                }
                
                // 降級為手動註冊
                $result = register_block_type( $block_config['name'], array(
                    'attributes' => $block_config['attributes'],
                    'render_callback' => $block_config['render_callback'],
                    'editor_script' => 'newebpay-blocks-editor',
                    'editor_style' => 'newebpay-blocks-editor',
                    'style' => 'newebpay-blocks-frontend',
                    'category' => 'newebpay'
                ) );
                
                if ( $result ) {
                    $registered_count++;
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        // Removed debug: Newebpay Blocks: Successfully registered block '{$block_config['name']}' manually
                        // error_log( "Newebpay Blocks: Successfully registered block '{$block_config['name']}' manually" );
                    }
                } else {
                    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                        // Removed debug: Newebpay Blocks: Failed to register block '{$block_config['name']}' manually
                        // error_log( "Newebpay Blocks: Failed to register block '{$block_config['name']}' manually" );
                    }
                }
            }
        }
        
        // 記錄成功註冊的訊息
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Removed debug: Newebpay Blocks: Successfully registered {$registered_count} out of {count} block types.
            // error_log( "Newebpay Blocks: Successfully registered {$registered_count} out of " . count( $this->blocks ) . " block types." );
        }
        
        // 如果沒有成功註冊任何區塊，記錄錯誤
        if ( $registered_count === 0 && count( $this->blocks ) > 0 ) {
            // Removed debug: Newebpay Blocks: Warning - No blocks were registered. Check WooCommerce installation and block definitions.
            // error_log( 'Newebpay Blocks: Warning - No blocks were registered. Check WooCommerce installation and block definitions.' );
        }
    }
    
    /**
     * 載入編輯器資源
     */
    public function enqueue_block_editor_assets() {
        // 只在編輯器頁面載入
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->base, array( 'post', 'page', 'edit-post', 'edit-page', 'site-editor' ) ) ) {
            return;
        }
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Removed debug: Newebpay Blocks: Enqueuing block editor assets
            // error_log( "Newebpay Blocks: Enqueuing block editor assets" );
            // Removed debug: Newebpay Blocks: Assets URL
            // error_log( "Newebpay Blocks: Assets URL: " . $this->assets_url );
        }
        
        $editor_js_path = $this->assets_url . '/js/blocks-editor.js';
        $editor_css_path = $this->assets_url . '/css/blocks-editor.css';
        
        // 檢查檔案是否存在 - 修正路徑計算
        $editor_js_file = $this->plugin_path . '/assets/js/blocks-editor.js';
        $editor_css_file = $this->plugin_path . '/assets/css/blocks-editor.css';
        
        // 只有檔案存在時才載入
        if ( ! file_exists( $editor_js_file ) || ! file_exists( $editor_css_file ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Removed debug: Newebpay Blocks: Editor assets not found, skipping enqueue
                // error_log( "Newebpay Blocks: Editor assets not found, skipping enqueue" );
            }
            return;
        }
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Removed debug: Newebpay Blocks: Editor JS file path
            // error_log( "Newebpay Blocks: Editor JS file path: " . $editor_js_file );
            // Removed debug: Newebpay Blocks: Editor JS exists
            // error_log( "Newebpay Blocks: Editor JS exists: " . ( file_exists( $editor_js_file ) ? 'Yes' : 'No' ) );
            // Removed debug: Newebpay Blocks: Editor CSS file path
            // error_log( "Newebpay Blocks: Editor CSS file path: " . $editor_css_file );
            // Removed debug: Newebpay Blocks: Editor CSS exists
            // error_log( "Newebpay Blocks: Editor CSS exists: " . ( file_exists( $editor_css_file ) ? 'Yes' : 'No' ) );
        }
        
        wp_enqueue_script(
            'newebpay-blocks-editor',
            $editor_js_path,
            array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', 'wp-components' ),
            '1.0.10',
            true
        );
        
        wp_enqueue_style(
            'newebpay-blocks-editor',
            $editor_css_path,
            array( 'wp-edit-blocks' ),
            '1.0.10'
        );
        
        // 傳遞資料給 JavaScript
        $localize_data = array(
            'nonce' => wp_create_nonce( 'newebpay_blocks_nonce' ),
            'apiUrl' => rest_url( 'newebpay/v1/' ),
            'blocks' => $this->blocks,
            'availableMethods' => $this->get_available_payment_methods()
        );
        
        wp_localize_script( 'newebpay-blocks-editor', 'newebpayBlocks', $localize_data );
        
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Removed debug: Newebpay Blocks: Successfully enqueued editor assets and localized data
            // error_log( "Newebpay Blocks: Successfully enqueued editor assets and localized data" );
        }
    }
    
    /**
     * 載入前端資源
     */
    public function enqueue_block_assets() {
        // 只在有使用區塊的頁面載入資源
        if ( ! $this->has_newebpay_blocks() ) {
            return;
        }
        
        $frontend_css_file = $this->plugin_path . '/assets/css/blocks-frontend.css';
        $frontend_js_file = $this->plugin_path . '/assets/js/blocks-frontend.js';
        
        // 只有檔案存在時才載入
        if ( file_exists( $frontend_css_file ) ) {
            wp_enqueue_style(
                'newebpay-blocks-frontend',
                $this->assets_url . '/css/blocks-frontend.css',
                array(),
                '1.0.10'
            );
        }
        
        if ( file_exists( $frontend_js_file ) ) {
            wp_enqueue_script(
                'newebpay-blocks-frontend',
                $this->assets_url . '/js/blocks-frontend.js',
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
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Removed debug: Newebpay Blocks: Adding block category
            // error_log( "Newebpay Blocks: Adding block category" );
        }
        
        $newebpay_category = array(
            'slug' => 'newebpay',
            'title' => __( 'Newebpay Payment', 'newebpay-payment' ),
            'icon' => 'money-alt',
        );
        
        // 檢查類別是否已存在
        $category_exists = false;
        foreach ( $categories as $category ) {
            if ( isset( $category['slug'] ) && $category['slug'] === 'newebpay' ) {
                $category_exists = true;
                break;
            }
        }
        
        if ( ! $category_exists ) {
            $categories[] = $newebpay_category;
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Removed debug: Newebpay Blocks: Successfully added block category 'newebpay'
                // error_log( "Newebpay Blocks: Successfully added block category 'newebpay'" );
            }
        } else {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Removed debug: Newebpay Blocks: Block category 'newebpay' already exists
                // error_log( "Newebpay Blocks: Block category 'newebpay' already exists" );
            }
        }
        
        return $categories;
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
        
        // 添加測試端點
        register_rest_route( 'newebpay/v1', '/test-payment-methods', array(
            'methods' => 'GET',
            'callback' => array( $this, 'api_get_test_payment_methods' ),
            'permission_callback' => array( $this, 'check_api_permissions' )
        ) );
        
        // 添加測試選擇端點
        register_rest_route( 'newebpay/v1', '/test-selection', array(
            'methods' => 'POST',
            'callback' => array( $this, 'api_test_selection' ),
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
            'source' => 'rest_api',
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
     * API: 測試付款方式 (供測試用)
     */
    public function api_get_test_payment_methods( $request ) {
        // 模擬啟用的付款方式，用於測試圖標顯示
        $test_methods = array();
        $all_methods = $this->get_all_payment_methods();
        
        // 選擇幾個付款方式進行測試
        $test_keys = array( 'credit', 'webatm', 'vacc' );
        
        foreach ( $test_keys as $key ) {
            if ( isset( $all_methods[ $key ] ) ) {
                $method = $all_methods[ $key ];
                $icon_url = $this->get_payment_method_icon_url( $method['icon'] );
                
                $test_methods[] = array_merge( $method, array(
                    'id' => $key,
                    'enabled' => true,
                    'setting_key' => strtoupper( $key ),
                    'icon' => $icon_url
                ) );
            }
        }
        
        return new WP_REST_Response( array(
            'success' => true,
            'data' => $test_methods,
            'count' => count( $test_methods ),
            'version' => '1.0.10',
            'note' => 'This is test data for icon display verification'
        ), 200 );
    }
    
    /**
     * API: 測試選擇提交 (供測試用)
     */
    public function api_test_selection( $request ) {
        $selected_method = $request->get_param( 'method' );
        $timestamp = current_time( 'mysql' );
        
        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Selection received successfully',
            'data' => array(
                'selected_method' => $selected_method,
                'timestamp' => $timestamp,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ),
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
        // 直接使用傳統 WC_newebpay 類別的 get_selected_payment() 取得相同的設定
        if ( class_exists( 'WC_newebpay' ) ) {
            $nwp_instance = new WC_newebpay();
            // 傳統方法回傳的格式為關聯陣列，鍵值為後端 key
            $selected = $nwp_instance->get_selected_payment();
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Removed debug: Newebpay Blocks: Retrieved selected from WC_newebpay
                // error_log( 'Newebpay Blocks: Retrieved selected from WC_newebpay: ' . print_r( $selected, true ) );
            }

            // 使用 convert_payment 中的 mapping 取得顯示用名稱
            $methods = array();
            foreach ( $selected as $key => $val ) {
                // 跳過 CVSCOMNotPayed，因為在前端通常單獨處理
                if ( $key === 'CVSCOMNotPayed' ) {
                    continue;
                }

                $methods[] = array(
                    'name' => $this->convert_backend_key_to_label( $key ),
                    'description' => '',
                    'id' => $key,
                    'enabled' => true,
                    'setting_key' => 'NwpPaymentMethod' . $key,
                    'backend_key' => $key
                );
            }

            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                // Removed debug: Newebpay Blocks: Final methods count (from WC_newebpay)
                // error_log( 'Newebpay Blocks: Final methods count (from WC_newebpay): ' . count( $methods ) );
            }
            return $methods;
        }

        // Fallback: 如果沒有 WC_newebpay 類別，回傳空陣列
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            // Removed debug: Newebpay Blocks: WC_newebpay class not available, returning empty.
            // error_log( 'Newebpay Blocks: WC_newebpay class not available, returning empty.' );
        }
        return array();
    }
    
    /**
     * 取得付款方式的設定鍵值
     */
    private function get_payment_method_setting_key( $method_key ) {
        // 直接對應到 Newebpay 設定中的鍵名
        return 'NwpPaymentMethod' . $method_key;
    }
    
    /**
     * 取得所有付款方式定義
     */
    private function get_all_payment_methods() {
        return array(
            'Credit' => array(
                'name' => __( '信用卡一次付清', 'newebpay-payment' ),
                'description' => __( '支援各大銀行信用卡', 'newebpay-payment' ),
                'icon' => 'credit-card'
            ),
            'AndroidPay' => array(
                'name' => __( 'Google Pay', 'newebpay-payment' ),
                'description' => __( 'Google Pay 行動支付', 'newebpay-payment' ),
                'icon' => 'google-pay'
            ),
            'SamsungPay' => array(
                'name' => __( 'Samsung Pay', 'newebpay-payment' ),
                'description' => __( 'Samsung Pay 行動支付', 'newebpay-payment' ),
                'icon' => 'samsung-pay'
            ),
            'LinePay' => array(
                'name' => __( 'Line Pay', 'newebpay-payment' ),
                'description' => __( 'Line Pay 行動支付', 'newebpay-payment' ),
                'icon' => 'line-pay'
            ),
            'Inst' => array(
                'name' => __( '信用卡分期', 'newebpay-payment' ),
                'description' => __( '信用卡分期付款', 'newebpay-payment' ),
                'icon' => 'credit-card'
            ),
            'CreditRed' => array(
                'name' => __( '信用卡紅利', 'newebpay-payment' ),
                'description' => __( '信用卡紅利折抵', 'newebpay-payment' ),
                'icon' => 'credit-card'
            ),
            'UnionPay' => array(
                'name' => __( '銀聯卡', 'newebpay-payment' ),
                'description' => __( '中國銀聯卡支付', 'newebpay-payment' ),
                'icon' => 'unionpay'
            ),
            'Webatm' => array(
                'name' => __( 'WEBATM', 'newebpay-payment' ),
                'description' => __( '使用讀卡機進行轉帳', 'newebpay-payment' ),
                'icon' => 'atm'
            ),
            'Vacc' => array(
                'name' => __( 'ATM轉帳', 'newebpay-payment' ),
                'description' => __( '虛擬帳號ATM轉帳', 'newebpay-payment' ),
                'icon' => 'bank'
            ),
            'CVS' => array(
                'name' => __( '超商代碼', 'newebpay-payment' ),
                'description' => __( '超商代碼繳費', 'newebpay-payment' ),
                'icon' => 'store'
            ),
            'BARCODE' => array(
                'name' => __( '超商條碼', 'newebpay-payment' ),
                'description' => __( '超商條碼繳費', 'newebpay-payment' ),
                'icon' => 'barcode'
            ),
            'EsunWallet' => array(
                'name' => __( '玉山 Wallet', 'newebpay-payment' ),
                'description' => __( '玉山銀行數位錢包', 'newebpay-payment' ),
                'icon' => 'wallet'
            ),
            'TaiwanPay' => array(
                'name' => __( '台灣 Pay', 'newebpay-payment' ),
                'description' => __( '台灣行動支付', 'newebpay-payment' ),
                'icon' => 'taiwan-pay'
            ),
            'BitoPay' => array(
                'name' => __( 'BitoPay', 'newebpay-payment' ),
                'description' => __( 'BitoPay 數位支付', 'newebpay-payment' ),
                'icon' => 'bito-pay'
            ),
            'EZPWECHAT' => array(
                'name' => __( '微信支付', 'newebpay-payment' ),
                'description' => __( '微信支付', 'newebpay-payment' ),
                'icon' => 'wechat'
            ),
            'EZPALIPAY' => array(
                'name' => __( '支付寶', 'newebpay-payment' ),
                'description' => __( '支付寶支付', 'newebpay-payment' ),
                'icon' => 'alipay'
            ),
            'CVSCOMPayed' => array(
                'name' => __( '超商取貨付款', 'newebpay-payment' ),
                'description' => __( '超商取貨付款', 'newebpay-payment' ),
                'icon' => 'shopping-bag'
            ),
            'CVSCOMNotPayed' => array(
                'name' => __( '超商取貨不付款', 'newebpay-payment' ),
                'description' => __( '超商取貨不付款', 'newebpay-payment' ),
                'icon' => 'package'
            ),
            'APPLEPAY' => array(
                'name' => __( 'Apple Pay', 'newebpay-payment' ),
                'description' => __( 'Apple Pay 行動支付', 'newebpay-payment' ),
                'icon' => 'apple-pay'
            ),
            'SmartPay' => array(
                'name' => __( '智慧ATM2.0', 'newebpay-payment' ),
                'description' => __( '智慧型ATM轉帳', 'newebpay-payment' ),
                'icon' => 'smartphone'
            ),
            'TWQR' => array(
                'name' => __( 'TWQR', 'newebpay-payment' ),
                'description' => __( '台灣QR碼支付', 'newebpay-payment' ),
                'icon' => 'qr-code'
            )
        );
    }
    
    /**
     * 驗證 Newebpay 設定
     */
    public function validate_newebpay_settings() {
        $settings = get_option( 'woocommerce_newebpay_settings' );
        
        if ( ! $settings || ! is_array( $settings ) ) {
            return array(
                'valid' => false,
                'message' => __( 'Newebpay 設定不存在或格式錯誤', 'newebpay-payment' ),
                'errors' => array( 'missing_settings' )
            );
        }
        
        $errors = array();
        $required_fields = array(
            'MerchantID' => __( '商店代號', 'newebpay-payment' ),
            'HashKey' => __( 'HashKey', 'newebpay-payment' ),
            'HashIV' => __( 'HashIV', 'newebpay-payment' )
        );
        
        // 檢查必要欄位
        foreach ( $required_fields as $field => $label ) {
            if ( empty( $settings[ $field ] ) ) {
                $errors[] = sprintf( __( '缺少必要設定: %s', 'newebpay-payment' ), $label );
            }
        }
        
        // 檢查是否有啟用的付款方式
        $payment_methods = array( 'CREDIT', 'WEBATM', 'VACC', 'CVS', 'BARCODE', 'smartPay', 'CVSCOM' );
        $enabled_methods = array();
        
        foreach ( $payment_methods as $method ) {
            if ( isset( $settings[ $method ] ) && $settings[ $method ] === 'yes' ) {
                $enabled_methods[] = $method;
            }
        }
        
        if ( empty( $enabled_methods ) ) {
            $errors[] = __( '沒有啟用任何付款方式', 'newebpay-payment' );
        }
        
        $is_valid = empty( $errors );
        
        return array(
            'valid' => $is_valid,
            'message' => $is_valid ? 
                __( 'Newebpay 設定驗證通過', 'newebpay-payment' ) : 
                __( 'Newebpay 設定驗證失敗', 'newebpay-payment' ),
            'errors' => $errors,
            'enabled_methods' => $enabled_methods,
            'test_mode' => isset( $settings['TestMode'] ) ? $settings['TestMode'] : 'no'
        );
    }
    
    /**
     * 生成付款方式 HTML
     */
    private function generate_payment_methods_html( $methods, $attributes ) {
        $layout = $attributes['layout'];
        $show_descriptions = $attributes['showDescriptions'];
        $show_icons = $attributes['showIcons'];
        
        // 主容器，使用前端 JavaScript 期望的 class 名稱
        $html = '<div class="newebpay-blocks-container newebpay-blocks--' . esc_attr( $layout ) . '" ';
        $html .= 'data-enable-selection="true" ';
        $html .= 'data-allow-multiple="false" ';
        $html .= 'data-enable-tooltips="true">';
        
        // 添加隱藏的輸入欄位來儲存選擇的付款方式
        $html .= '<input type="hidden" name="newebpay_selected_method" id="newebpay_selected_method" value="" />';
        
        foreach ( $methods as $method ) {
            // 付款方式容器，使用前端 JavaScript 期望的結構
            $html .= '<div class="newebpay-method newebpay-method--' . esc_attr( $layout ) . '" ';
            $html .= 'data-method-id="' . esc_attr( $method['id'] ) . '" ';
            $html .= 'data-method-type="' . esc_attr( strtoupper( $method['id'] ) ) . '" ';
            $html .= 'tabindex="0" role="button">';
            
            // 圖標
            if ( $show_icons && ! empty( $method['icon'] ) ) {
                $html .= '<div class="newebpay-method__icon">';
                $html .= '<img src="' . esc_url( $method['icon'] ) . '" ';
                $html .= 'alt="' . esc_attr( $method['name'] ) . '" ';
                $html .= 'style="max-width: 40px; height: auto;">';
                $html .= '</div>';
            }
            
            // 標題
            $html .= '<div class="newebpay-method__title">';
            $html .= esc_html( $method['name'] );
            $html .= '</div>';
            
            // 描述
            if ( $show_descriptions && ! empty( $method['description'] ) ) {
                $html .= '<div class="newebpay-method__description">';
                $html .= esc_html( $method['description'] );
                $html .= '</div>';
            }
            
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
     * 取得付款方式圖標的 URL
     */
    private function get_payment_method_icon_url( $icon_identifier ) {
        // 圖標映射表：標識符 => 文件名
        $icon_map = array(
            'credit-card' => 'credit-card.png',
            'atm'         => 'atm.png',
            'bank'        => 'bank.png',
            'store'       => 'store.png',
            'barcode'     => 'barcode.png',
            'smartphone'  => 'smartphone.png'
        );
        
        // 如果有對應的圖標文件，返回完整 URL
        if ( isset( $icon_map[ $icon_identifier ] ) ) {
            return $this->plugin_url . 'assets/images/' . $icon_map[ $icon_identifier ];
        }
        
        // 如果沒有對應的圖標，使用預設的 newebpay logo
        return $this->plugin_url . 'assets/images/newebpay-logo.png';
    }

    /**
     * 載入管理功能
     */
    private function load_admin() {
        if ( is_admin() ) {
            include_once $this->blocks_path . '/class-newebpay-blocks-admin.php';
        }
    }
    
    /**
     * 渲染測試短碼
     */
    public function render_test_shortcode( $atts ) {
        // 設定默認屬性
        $attributes = shortcode_atts( array(
            'layout' => 'grid',
            'showIcons' => true,
            'showDescriptions' => true
        ), $atts );
        
        // 轉換字符串為布爾值
        $attributes['showIcons'] = filter_var( $attributes['showIcons'], FILTER_VALIDATE_BOOLEAN );
        $attributes['showDescriptions'] = filter_var( $attributes['showDescriptions'], FILTER_VALIDATE_BOOLEAN );
        
        // 獲取測試付款方式
        $test_methods = array(
            array(
                'id' => 'credit',
                'name' => '信用卡',
                'description' => '支援各大銀行信用卡',
                'icon' => $this->plugin_url . 'assets/images/credit-card.png'
            ),
            array(
                'id' => 'webatm',
                'name' => '網路ATM',
                'description' => '使用讀卡機進行轉帳',
                'icon' => $this->plugin_url . 'assets/images/atm.png'
            ),
            array(
                'id' => 'vacc',
                'name' => 'ATM轉帳',
                'description' => '虛擬帳號ATM轉帳',
                'icon' => $this->plugin_url . 'assets/images/bank.png'
            )
        );
        
        // 生成 HTML
        $html = $this->generate_payment_methods_html( $test_methods, $attributes );
        
        // 添加測試表單
        $html .= '<div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px;">';
        $html .= '<h4>測試結果:</h4>';
        $html .= '<p>選擇的付款方式: <span id="selected-method-display">尚未選擇</span></p>';
        $html .= '<button type="button" onclick="testSelection()">測試提交</button>';
        $html .= '</div>';
        
        // 添加測試 JavaScript
        $html .= '<script>
        document.addEventListener("newebpayMethodSelection", function(event) {
            const display = document.getElementById("selected-method-display");
            if (display) {
                display.textContent = event.detail.isSelected ? event.detail.methodId : "尚未選擇";
            }
        });
        
        function testSelection() {
            const selectedMethod = document.getElementById("newebpay_selected_method");
            if (selectedMethod && selectedMethod.value) {
                alert("選擇的付款方式: " + selectedMethod.value);
                
                // 測試 API 提交
                fetch("/wp-json/newebpay/v1/test-selection", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({
                        method: selectedMethod.value
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log("API Response:", data);
                    alert("API 測試成功: " + JSON.stringify(data));
                })
                .catch(error => {
                    console.error("API Error:", error);
                    alert("API 測試失敗: " + error.message);
                });
            } else {
                alert("請先選擇付款方式！");
            }
        }
        </script>';
        
        return $html;
    }
}