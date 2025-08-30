<?php
/**
 * WordPress 編輯器環境中的區塊測試
 * 在 WordPress 後台 → 外觀 → 佈景主題編輯器 → functions.php 中加入這段程式碼
 */

add_action( 'admin_init', function() {
    if ( isset( $_GET['test_newebpay_blocks'] ) && current_user_can( 'manage_options' ) ) {
        echo '<div style="background: white; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
        echo '<h2>Newebpay Blocks 即時測試</h2>';
        
        // 測試 1: 檢查類別是否載入
        if ( class_exists( 'Newebpay_Blocks' ) ) {
            echo '<p style="color: green;">✅ Newebpay_Blocks 類別已載入</p>';
            
            $instance = Newebpay_Blocks::get_instance();
            echo '<p style="color: green;">✅ 實例創建成功</p>';
            
        } else {
            echo '<p style="color: red;">❌ Newebpay_Blocks 類別未載入</p>';
        }
        
        // 測試 2: 檢查區塊是否在 WordPress 中註冊
        if ( function_exists( 'WP_Block_Type_Registry' ) ) {
            $registry = WP_Block_Type_Registry::get_instance();
            $registered_blocks = $registry->get_all_registered();
            
            if ( isset( $registered_blocks['newebpay/payment-methods'] ) ) {
                echo '<p style="color: green;">✅ newebpay/payment-methods 區塊已註冊</p>';
                echo '<p>區塊詳情：<pre>' . print_r( $registered_blocks['newebpay/payment-methods'], true ) . '</pre></p>';
            } else {
                echo '<p style="color: orange;">⚠️ newebpay/payment-methods 區塊未註冊</p>';
                echo '<p>已註冊的區塊總數：' . count( $registered_blocks ) . '</p>';
                
                // 檢查是否有任何 newebpay 相關區塊
                $newebpay_blocks = array_filter( array_keys( $registered_blocks ), function( $name ) {
                    return strpos( $name, 'newebpay' ) !== false;
                });
                
                if ( ! empty( $newebpay_blocks ) ) {
                    echo '<p>找到的 Newebpay 區塊：' . implode( ', ', $newebpay_blocks ) . '</p>';
                } else {
                    echo '<p>沒有找到任何 Newebpay 區塊</p>';
                }
            }
        }
        
        // 測試 3: 檢查 WooCommerce 狀態
        if ( class_exists( 'WooCommerce' ) ) {
            echo '<p style="color: green;">✅ WooCommerce 已啟用</p>';
        } else {
            echo '<p style="color: red;">❌ WooCommerce 未啟用 - 這可能是區塊未註冊的原因</p>';
        }
        
        // 測試 4: 檢查 Newebpay 設定
        $newebpay_settings = get_option( 'woocommerce_newebpay_settings', array() );
        if ( ! empty( $newebpay_settings ) && isset( $newebpay_settings['enabled'] ) && $newebpay_settings['enabled'] === 'yes' ) {
            echo '<p style="color: green;">✅ Newebpay 設定已啟用</p>';
        } else {
            echo '<p style="color: orange;">⚠️ Newebpay 設定未完成</p>';
            echo '<p>請到 WooCommerce → 設定 → 付款 → Newebpay 完成設定</p>';
        }
        
        // 測試 5: 手動觸發區塊註冊
        echo '<h3>手動觸發區塊註冊：</h3>';
        if ( class_exists( 'Newebpay_Blocks' ) ) {
            $instance = Newebpay_Blocks::get_instance();
            
            // 使用反射來調用註冊方法
            try {
                $reflection = new ReflectionClass( $instance );
                $method = $reflection->getMethod( 'register_block_types' );
                $method->setAccessible( true );
                $method->invoke( $instance );
                echo '<p style="color: green;">✅ 手動觸發區塊註冊完成</p>';
            } catch ( Exception $e ) {
                echo '<p style="color: red;">❌ 手動註冊失敗：' . $e->getMessage() . '</p>';
            }
        }
        
        echo '<p><strong>建議檢查步驟：</strong></p>';
        echo '<ol>';
        echo '<li>確保 WooCommerce 插件已啟用</li>';
        echo '<li>到 WooCommerce → 設定 → 付款 → Newebpay 完成基本設定</li>';
        echo '<li>在區塊編輯器中尋找 "Newebpay" 分類</li>';
        echo '<li>檢查瀏覽器開發者工具的控制台是否有錯誤</li>';
        echo '</ol>';
        
        echo '</div>';
        exit;
    }
});

// 在管理後台加入測試連結
add_action( 'admin_notices', function() {
    if ( current_user_can( 'manage_options' ) ) {
        echo '<div class="notice notice-info">';
        echo '<p><strong>Newebpay Blocks 測試：</strong> ';
        echo '<a href="' . admin_url( '?test_newebpay_blocks=1' ) . '" target="_blank">執行區塊系統測試</a>';
        echo '</p></div>';
    }
});
?>
