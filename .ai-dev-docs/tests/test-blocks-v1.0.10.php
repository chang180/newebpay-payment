<?php
/**
 * Newebpay Blocks 功能測試腳本
 * 
 * @package NewebpayPayment
 * @since 1.0.10
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_Blocks_Test {
    
    public static function run_tests() {
        $results = array();
        
        // 測試 1: 檢查類別是否存在
        $results['class_exists'] = class_exists( 'Newebpay_Blocks' );
        
        // 測試 2: 檢查單例模式
        if ( $results['class_exists'] ) {
            $instance1 = Newebpay_Blocks::get_instance();
            $instance2 = Newebpay_Blocks::get_instance();
            $results['singleton'] = $instance1 === $instance2;
        }
        
        // 測試 3: 檢查區塊註冊
        $results['blocks_registered'] = self::check_blocks_registered();
        
        // 測試 4: 檢查設定可用性
        $results['settings_available'] = self::check_newebpay_settings();
        
        // 測試 5: 檢查資源檔案
        $results['assets_exist'] = self::check_asset_files();
        
        return $results;
    }
    
    private static function check_blocks_registered() {
        $registry = WP_Block_Type_Registry::get_instance();
        return $registry->is_registered( 'newebpay/payment-methods' );
    }
    
    private static function check_newebpay_settings() {
        $settings = get_option( 'woocommerce_nwp_settings' );
        return is_array( $settings ) && ! empty( $settings );
    }
    
    private static function check_asset_files() {
        $blocks_path = NEWEB_MAIN_PATH . '/includes/blocks/assets';
        
        $required_files = array(
            'js/blocks-editor.js',
            'js/blocks-frontend.js',
            'css/blocks-editor.css',
            'css/blocks-frontend.css'
        );
        
        foreach ( $required_files as $file ) {
            if ( ! file_exists( $blocks_path . '/' . $file ) ) {
                return false;
            }
        }
        
        return true;
    }
    
    public static function display_test_results() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $results = self::run_tests();
        
        echo '<div class="notice notice-info">';
        echo '<h3>Newebpay Blocks v1.0.10 測試結果</h3>';
        echo '<ul>';
        
        foreach ( $results as $test => $passed ) {
            $status = $passed ? '✅ 通過' : '❌ 失敗';
            $test_name = self::get_test_name( $test );
            echo '<li><strong>' . $test_name . '</strong>: ' . $status . '</li>';
        }
        
        echo '</ul>';
        
        $all_passed = ! in_array( false, $results, true );
        if ( $all_passed ) {
            echo '<p style="color: green; font-weight: bold;">🎉 所有測試通過！Blocks 功能已成功整合。</p>';
        } else {
            echo '<p style="color: red; font-weight: bold;">⚠️ 有測試失敗，請檢查相關設定。</p>';
        }
        
        echo '</div>';
    }
    
    private static function get_test_name( $test ) {
        $names = array(
            'class_exists' => '類別載入測試',
            'singleton' => '單例模式測試',
            'blocks_registered' => '區塊註冊測試',
            'settings_available' => 'Newebpay 設定檢查',
            'assets_exist' => '資源檔案檢查'
        );
        
        return isset( $names[ $test ] ) ? $names[ $test ] : $test;
    }
}

// 在管理後台顯示測試結果
if ( is_admin() && isset( $_GET['newebpay_test'] ) ) {
    add_action( 'admin_notices', array( 'Newebpay_Blocks_Test', 'display_test_results' ) );
}
