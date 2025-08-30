<?php
/**
 * 簡單的區塊測試頁面
 * 在瀏覽器中訪問：http://your-site/wp-content/plugins/newebpay-payment/includes/blocks/quick-test.php
 */

// 載入 WordPress
$wp_load_path = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
if ( file_exists( $wp_load_path ) ) {
    require_once $wp_load_path;
} else {
    die( 'WordPress not found. Please check the path.' );
}

// 確保只有管理員可以訪問
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have permission to access this page.' );
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Newebpay Blocks 快速測試</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #0073aa; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f0f0f0; padding: 10px; overflow: auto; }
    </style>
</head>
<body>
    <h1>🧪 Newebpay Blocks 快速測試</h1>
    
    <div class="test-section">
        <h2>1. WordPress 環境檢查</h2>
        <?php
        echo '<p>WordPress 版本: ' . get_bloginfo( 'version' ) . '</p>';
        echo '<p>PHP 版本: ' . phpversion() . '</p>';
        echo '<p>當前用戶: ' . wp_get_current_user()->user_login . '</p>';
        
        if ( function_exists( 'register_block_type' ) ) {
            echo '<p class="success">✅ Gutenberg 區塊 API 可用</p>';
        } else {
            echo '<p class="error">❌ Gutenberg 區塊 API 不可用</p>';
        }
        
        if ( class_exists( 'WooCommerce' ) ) {
            echo '<p class="success">✅ WooCommerce 已啟用</p>';
        } else {
            echo '<p class="warning">⚠️ WooCommerce 未啟用</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. Newebpay Blocks 類別檢查</h2>
        <?php
        if ( class_exists( 'Newebpay_Blocks' ) ) {
            echo '<p class="success">✅ Newebpay_Blocks 類別已載入</p>';
            
            try {
                $instance = Newebpay_Blocks::get_instance();
                echo '<p class="success">✅ 實例創建成功</p>';
                
                // 檢查實例方法
                $methods = array( 'register_block_types', 'enqueue_block_editor_assets', 'get_available_payment_methods' );
                foreach ( $methods as $method ) {
                    if ( method_exists( $instance, $method ) ) {
                        echo '<p class="success">✅ 方法存在: ' . $method . '</p>';
                    } else {
                        echo '<p class="error">❌ 方法不存在: ' . $method . '</p>';
                    }
                }
                
            } catch ( Exception $e ) {
                echo '<p class="error">❌ 實例創建失敗: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p class="error">❌ Newebpay_Blocks 類別未載入</p>';
            echo '<p>檢查插件是否正確啟用</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. 已註冊區塊檢查</h2>
        <?php
        if ( function_exists( 'WP_Block_Type_Registry' ) ) {
            $registry = WP_Block_Type_Registry::get_instance();
            $blocks = $registry->get_all_registered();
            
            echo '<p>已註冊區塊總數: ' . count( $blocks ) . '</p>';
            
            // 檢查 Newebpay 區塊
            $newebpay_blocks = array();
            foreach ( $blocks as $name => $block ) {
                if ( strpos( $name, 'newebpay' ) !== false ) {
                    $newebpay_blocks[] = $name;
                }
            }
            
            if ( ! empty( $newebpay_blocks ) ) {
                echo '<p class="success">✅ 找到 Newebpay 區塊:</p>';
                echo '<ul>';
                foreach ( $newebpay_blocks as $block_name ) {
                    echo '<li>' . $block_name . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p class="warning">⚠️ 沒有找到 Newebpay 區塊</p>';
                echo '<p>這可能是因為區塊只在編輯器環境中註冊</p>';
            }
            
            // 顯示一些已註冊的區塊作為參考
            echo '<h4>部分已註冊區塊 (前10個):</h4>';
            echo '<ul>';
            $count = 0;
            foreach ( array_keys( $blocks ) as $block_name ) {
                if ( $count >= 10 ) break;
                echo '<li>' . $block_name . '</li>';
                $count++;
            }
            echo '</ul>';
            
        } else {
            echo '<p class="error">❌ WP_Block_Type_Registry 不可用</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>4. Newebpay 設定檢查</h2>
        <?php
        $settings = get_option( 'woocommerce_newebpay_settings', array() );
        
        if ( ! empty( $settings ) ) {
            echo '<p class="success">✅ 找到 Newebpay 設定</p>';
            echo '<pre>' . print_r( $settings, true ) . '</pre>';
        } else {
            echo '<p class="warning">⚠️ Newebpay 設定為空</p>';
            echo '<p>請到 WooCommerce → 設定 → 付款 → Newebpay 完成設定</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>5. 手動測試區塊註冊</h2>
        <?php
        if ( isset( $_GET['manual_register'] ) ) {
            echo '<h4>執行手動註冊...</h4>';
            
            if ( class_exists( 'Newebpay_Blocks' ) ) {
                try {
                    $instance = Newebpay_Blocks::get_instance();
                    
                    // 手動調用註冊方法
                    do_action( 'init' );
                    
                    echo '<p class="success">✅ 手動觸發 init 完成</p>';
                    
                    // 再次檢查
                    if ( function_exists( 'WP_Block_Type_Registry' ) ) {
                        $registry = WP_Block_Type_Registry::get_instance();
                        $blocks = $registry->get_all_registered();
                        
                        if ( isset( $blocks['newebpay/payment-methods'] ) ) {
                            echo '<p class="success">✅ newebpay/payment-methods 現在已註冊!</p>';
                        } else {
                            echo '<p class="warning">⚠️ 區塊仍未註冊</p>';
                        }
                    }
                    
                } catch ( Exception $e ) {
                    echo '<p class="error">❌ 手動註冊失敗: ' . $e->getMessage() . '</p>';
                }
            }
        } else {
            echo '<p><a href="?manual_register=1">點擊進行手動註冊測試</a></p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>6. 建議的解決步驟</h2>
        <ol>
            <li><strong>確認 WooCommerce 已啟用</strong> - 區塊系統依賴 WooCommerce</li>
            <li><strong>完成 Newebpay 基本設定</strong> - 到 WooCommerce → 設定 → 付款 → Newebpay</li>
            <li><strong>在區塊編輯器中測試</strong> - 建立新頁面或文章，在編輯器中尋找 "Newebpay" 分類</li>
            <li><strong>檢查瀏覽器控制台</strong> - 查看是否有 JavaScript 錯誤</li>
            <li><strong>清除快取</strong> - 如果使用快取插件，請清除快取</li>
        </ol>
    </div>
    
    <div class="test-section">
        <h2>7. 快速動作</h2>
        <p>
            <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=newebpay' ); ?>">前往 Newebpay 設定</a> |
            <a href="<?php echo admin_url( 'post-new.php?post_type=page' ); ?>">建立新頁面測試區塊</a> |
            <a href="<?php echo admin_url( 'themes.php?page=gutenberg-widgets' ); ?>">區塊小工具編輯器</a>
        </p>
    </div>
    
    <hr>
    <p><small>測試時間: <?php echo current_time( 'Y-m-d H:i:s' ); ?></small></p>
</body>
</html>
