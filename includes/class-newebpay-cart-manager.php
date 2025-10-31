<?php
/**
 * Newebpay 購物車管理類別
 * 優化購物車清空邏輯，減少資料庫查詢
 * 
 * @package NeWebPay_Payment
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_Cart_Manager {
    
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
        $this->init_hooks();
    }
    
    /**
     * 初始化 Hook
     */
    private function init_hooks() {
        // 付款完成時清空購物車
        add_action( 'woocommerce_payment_complete', array( $this, 'on_payment_complete' ), 10, 1 );
        
        // 訂單狀態變更時處理
        add_action( 'woocommerce_order_status_processing', array( $this, 'on_order_status_processing' ), 10, 1 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'on_order_status_completed' ), 10, 1 );
        
        // 在 thankyou 頁面強制清空
        add_action( 'woocommerce_thankyou', array( $this, 'force_clear_cart_on_thankyou' ), 10, 1 );
        
        // 檢查後端回調標記
        add_action( 'wp_loaded', array( $this, 'check_and_clear_cart_from_backend_callback' ) );
    }
    
    /**
     * 付款完成時處理購物車
     * 
     * @param int $order_id 訂單 ID
     */
    public function on_payment_complete( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order || $order->get_payment_method() !== 'newebpay' ) {
            return;
        }
        
        $this->clear_cart_for_order( $order_id );
    }
    
    /**
     * 訂單狀態變為處理中時處理購物車
     * 
     * @param int $order_id 訂單 ID
     */
    public function on_order_status_processing( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order || $order->get_payment_method() !== 'newebpay' ) {
            return;
        }
        
        $this->clear_cart_for_order( $order_id );
    }
    
    /**
     * 訂單狀態變為已完成時處理購物車
     * 
     * @param int $order_id 訂單 ID
     */
    public function on_order_status_completed( $order_id ) {
        $order = wc_get_order( $order_id );
        
        if ( ! $order || $order->get_payment_method() !== 'newebpay' ) {
            return;
        }
        
        $this->clear_cart_for_order( $order_id );
    }
    
    /**
     * 在 thankyou 頁面強制清空購物車
     * 
     * @param int $order_id 訂單 ID
     */
    public function force_clear_cart_on_thankyou( $order_id ) {
        if ( ! $order_id ) {
            return;
        }
        
        $order = wc_get_order( $order_id );
        if ( ! $order || $order->get_payment_method() !== 'newebpay' ) {
            return;
        }
        
        // 只在訂單已付款或處理中時清空購物車
        if ( $order->is_paid() || in_array( $order->get_status(), array( 'processing', 'completed' ) ) ) {
            $this->clear_cart_for_order( $order_id, true );
        }
    }
    
    /**
     * 檢查後端回調標記並清空購物車
     */
    public function check_and_clear_cart_from_backend_callback() {
        // 只在前端執行，且用戶已登入時執行
        if ( is_admin() || ! is_user_logged_in() ) {
            return;
        }
        
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return;
        }
        
        // 查找用戶最近的 newebpay 訂單
        $recent_orders = $this->get_user_recent_orders( $user_id );
        
        foreach ( $recent_orders as $order ) {
            $clear_flag = get_transient( 'newebpay_clear_cart_' . $order->get_id() );
            if ( $clear_flag ) {
                // 刪除標記
                delete_transient( 'newebpay_clear_cart_' . $order->get_id() );
                
                // 清空購物車
                $this->clear_cart_for_order( $order->get_id() );
                
                // 只處理一個訂單即可
                break;
            }
        }
    }
    
    /**
     * 為特定訂單清空購物車
     * 
     * @param int $order_id 訂單 ID
     * @param bool $force 是否強制清空
     */
    public function clear_cart_for_order( $order_id, $force = false ) {
        // 檢查 WooCommerce 是否已載入
        if ( ! function_exists( 'WC' ) || ! WC() ) {
            return;
        }
        
        // 檢查 WooCommerce 購物車是否可用
        if ( ! WC()->cart ) {
            return;
        }
        
        // 如果購物車為空且不是強制清空，則無需處理
        if ( ! $force && WC()->cart->is_empty() ) {
            return;
        }
        
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }
        
        // 檢查是否需要清空購物車
        if ( ! $force && ! $this->should_clear_cart_for_order( $order ) ) {
            return;
        }
        
        // 執行購物車清空
        $this->perform_cart_clear( $order );
    }
    
    /**
     * 檢查是否應該為訂單清空購物車
     * 
     * @param WC_Order $order 訂單物件
     * @return bool 是否應該清空
     */
    private function should_clear_cart_for_order( $order ) {
        // 檢查訂單狀態
        if ( ! in_array( $order->get_status(), array( 'processing', 'completed' ) ) ) {
            return false;
        }
        
        // 檢查是否有交易 ID（表示付款成功）
        if ( empty( $order->get_transaction_id() ) ) {
            return false;
        }
        
        // 檢查購物車中是否有與訂單相同的商品
        return $this->has_matching_products_in_cart( $order );
    }
    
    /**
     * 檢查購物車中是否有與訂單相同的商品
     * 
     * @param WC_Order $order 訂單物件
     * @return bool 是否有相同商品
     */
    private function has_matching_products_in_cart( $order ) {
        if ( WC()->cart->is_empty() ) {
            return false;
        }
        
        // 獲取訂單商品 ID
        $order_product_ids = $this->get_order_product_ids( $order );
        
        // 獲取購物車商品 ID
        $cart_product_ids = $this->get_cart_product_ids();
        
        // 檢查是否有重疊的商品
        $matching_products = array_intersect( $order_product_ids, $cart_product_ids );
        
        return ! empty( $matching_products );
    }
    
    /**
     * 獲取訂單商品 ID
     * 
     * @param WC_Order $order 訂單物件
     * @return array 商品 ID 陣列
     */
    private function get_order_product_ids( $order ) {
        $product_ids = array();
        
        foreach ( $order->get_items() as $item ) {
            // 使用 WooCommerce 訂單項目的 meta data 方式
            $product_id = $item->get_meta( '_product_id' );
            $variation_id = $item->get_meta( '_variation_id' );
            
            if ( $product_id ) {
                $product_ids[] = intval( $product_id );
            }
            
            if ( $variation_id ) {
                $product_ids[] = intval( $variation_id );
            }
        }
        
        return array_filter( $product_ids );
    }
    
    /**
     * 獲取購物車商品 ID
     * 
     * @return array 商品 ID 陣列
     */
    private function get_cart_product_ids() {
        $product_ids = array();
        
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $product_ids[] = $cart_item['product_id'];
            if ( ! empty( $cart_item['variation_id'] ) ) {
                $product_ids[] = $cart_item['variation_id'];
            }
        }
        
        return array_filter( $product_ids );
    }
    
    /**
     * 執行購物車清空
     * 
     * @param WC_Order $order 訂單物件
     */
    private function perform_cart_clear( $order ) {
        // 清空購物車
        WC()->cart->empty_cart();
        
        // 清空 session
        if ( WC()->session ) {
            WC()->session->set( 'cart', array() );
            WC()->session->set( 'cart_totals', null );
        }
        
        // 針對登入用戶的特殊處理
        if ( is_user_logged_in() ) {
            $this->clear_persistent_cart( get_current_user_id() );
        }
        
        // 添加 JavaScript 來確保前端購物車圖示也更新
        $this->add_frontend_cart_update_script( $order->get_id() );
        
        // 記錄清空操作
        $this->log_cart_clear( $order->get_id() );
    }
    
    /**
     * 清空持久化購物車
     * 
     * @param int $user_id 用戶 ID
     */
    private function clear_persistent_cart( $user_id ) {
        $blog_id = get_current_blog_id();
        
        // 清空用戶的持久化購物車
        delete_user_meta( $user_id, '_woocommerce_persistent_cart_' . $blog_id );
        delete_user_meta( $user_id, '_woocommerce_persistent_cart' );
        
        // 清空可能的其他購物車相關 meta
        delete_user_meta( $user_id, 'wc_cart_hash_' . md5( $blog_id ) );
    }
    
    /**
     * 添加前端購物車更新腳本
     * 
     * @param int $order_id 訂單 ID
     */
    private function add_frontend_cart_update_script( $order_id ) {
        add_action( 'wp_footer', function() use ( $order_id ) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // 強制更新購物車計數器和相關元素
                $('.cart-contents-count, .cart-count, .cart-contents').text('0');
                $('.cart-contents-total, .cart-total').text('');
                $('.cart-empty').show();
                
                // 更新購物車圖標的所有可能選擇器
                $('.woocommerce-mini-cart__total, .cart-subtotal').hide();
                $('.mini-cart-counter').text('0');
                
                // 強制重新載入購物車片段
                if (typeof wc_cart_fragments_params !== 'undefined') {
                    $.ajax({
                        type: 'POST',
                        url: wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'),
                        data: {},
                        success: function(data) {
                            if (data && data.fragments) {
                                $.each(data.fragments, function(key, value) {
                                    $(key).replaceWith(value);
                                });
                            }
                        }
                    });
                }
                
                // 觸發所有可能的購物車更新事件
                $(document.body).trigger('wc_fragment_refresh');
                $(document.body).trigger('updated_wc_div');
                $(document.body).trigger('wc_fragments_refreshed');
                $(document.body).trigger('cart_page_refreshed');
                
                console.log('Newebpay: Cart cleared for order #<?php echo esc_js( $order_id ); ?>');
            });
            </script>
            <?php
        });
    }
    
    /**
     * 獲取用戶最近的訂單
     * 
     * @param int $user_id 用戶 ID
     * @return array 訂單陣列
     */
    private function get_user_recent_orders( $user_id ) {
        return wc_get_orders( array(
            'customer_id' => $user_id,
            'payment_method' => 'newebpay',
            'status' => array( 'processing', 'completed' ),
            'limit' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ) );
    }
    
    /**
     * 記錄購物車清空操作
     * 
     * @param int $order_id 訂單 ID
     */
    private function log_cart_clear( $order_id ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            $logger->info( 'Cart cleared for order', array(
                'source' => 'newebpay-payment',
                'order_id' => $order_id,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time( 'Y-m-d H:i:s' )
            ) );
        }
    }
    
    /**
     * 設置後端回調清空標記
     * 
     * @param int $order_id 訂單 ID
     */
    public function set_backend_callback_clear_flag( $order_id ) {
        set_transient( 'newebpay_clear_cart_' . $order_id, time(), 3600 );
    }
}
