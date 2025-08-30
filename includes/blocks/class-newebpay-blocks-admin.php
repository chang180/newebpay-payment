<?php
/**
 * Newebpay Blocks 管理頁面
 * 
 * @package NewebpayPayment
 * @since 1.0.10
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_Blocks_Admin {
    
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_actions' ) );
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Newebpay Blocks',
            'Newebpay Blocks',
            'manage_woocommerce',
            'newebpay-blocks',
            array( __CLASS__, 'admin_page' )
        );
    }
    
    public static function handle_actions() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'newebpay-blocks' ) {
            return;
        }
        
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'test' ) {
            // 載入測試腳本
            include_once NEWEB_MAIN_PATH . '/.ai-dev-docs/tests/test-blocks-v1.0.10.php';
        }
    }
    
    public static function admin_page() {
        ?>
        <div class="wrap">
            <h1>Newebpay Blocks 管理</h1>
            
            <div class="notice notice-info">
                <p><strong>版本:</strong> 1.0.10 | <strong>狀態:</strong> 開發中</p>
            </div>
            
            <div class="card">
                <h2>功能測試</h2>
                <p>點擊下方按鈕執行 Blocks 功能測試：</p>
                <a href="<?php echo admin_url( 'admin.php?page=newebpay-blocks&action=test' ); ?>" 
                   class="button button-primary">執行測試</a>
            </div>
            
            <div class="card">
                <h2>可用區塊</h2>
                <?php self::display_available_blocks(); ?>
            </div>
            
            <div class="card">
                <h2>付款方式狀態</h2>
                <?php self::display_payment_methods_status(); ?>
            </div>
            
            <div class="card">
                <h2>快速連結</h2>
                <ul>
                    <li><a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nwp' ); ?>">Newebpay 設定</a></li>
                    <li><a href="<?php echo admin_url( 'post-new.php?post_type=page' ); ?>">建立新頁面 (測試區塊)</a></li>
                    <li><a href="<?php echo admin_url( 'edit.php?post_type=page' ); ?>">管理頁面</a></li>
                </ul>
            </div>
        </div>
        
        <?php
        // 如果有測試動作，顯示測試結果
        if ( isset( $_GET['action'] ) && $_GET['action'] === 'test' ) {
            if ( class_exists( 'Newebpay_Blocks_Test' ) ) {
                Newebpay_Blocks_Test::display_test_results();
            }
        }
    }
    
    private static function display_available_blocks() {
        $blocks_instance = Newebpay_Blocks::get_instance();
        $registry = WP_Block_Type_Registry::get_instance();
        
        $newebpay_blocks = array(
            'newebpay/payment-methods' => 'Newebpay 付款方式'
        );
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>區塊名稱</th><th>區塊 ID</th><th>狀態</th></tr></thead>';
        echo '<tbody>';
        
        foreach ( $newebpay_blocks as $block_id => $block_name ) {
            $is_registered = $registry->is_registered( $block_id );
            $status = $is_registered ? '<span style="color: green;">✅ 已註冊</span>' : '<span style="color: red;">❌ 未註冊</span>';
            
            echo '<tr>';
            echo '<td>' . esc_html( $block_name ) . '</td>';
            echo '<td><code>' . esc_html( $block_id ) . '</code></td>';
            echo '<td>' . $status . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private static function display_payment_methods_status() {
        $nwp_settings = get_option( 'woocommerce_nwp_settings', array() );
        
        if ( empty( $nwp_settings ) ) {
            echo '<p style="color: red;">⚠️ Newebpay 設定未找到。請先設定 Newebpay Payment 插件。</p>';
            return;
        }
        
        $all_methods = array(
            'credit' => '信用卡',
            'webatm' => '網路ATM',
            'vacc' => 'ATM轉帳',
            'cvs' => '超商代碼',
            'barcode' => '超商條碼',
            'SmartPay' => '智慧ATM2.0'
        );
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>付款方式</th><th>狀態</th><th>在區塊中可用</th></tr></thead>';
        echo '<tbody>';
        
        foreach ( $all_methods as $key => $name ) {
            $enabled = isset( $nwp_settings[ $key ] ) && $nwp_settings[ $key ] === 'yes';
            $status = $enabled ? '<span style="color: green;">✅ 啟用</span>' : '<span style="color: gray;">⭕ 停用</span>';
            $block_available = $enabled ? '<span style="color: green;">✅ 可用</span>' : '<span style="color: gray;">⭕ 不可用</span>';
            
            echo '<tr>';
            echo '<td>' . esc_html( $name ) . '</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . $block_available . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        $enabled_count = 0;
        foreach ( $all_methods as $key => $name ) {
            if ( isset( $nwp_settings[ $key ] ) && $nwp_settings[ $key ] === 'yes' ) {
                $enabled_count++;
            }
        }
        
        echo '<p><strong>總計:</strong> ' . $enabled_count . ' 個付款方式已啟用並可在區塊中使用。</p>';
    }
}

// 初始化管理頁面
if ( is_admin() ) {
    Newebpay_Blocks_Admin::init();
}
