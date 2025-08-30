<?php
/**
 * Newebpay Blocks 測試驗證器
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_Blocks_Test_Runner {
    
    /**
     * 執行所有測試
     */
    public static function run_all_tests() {
        $results = array();
        
        // 測試 1: 類別載入測試
        $results['class_loading'] = self::test_class_loading();
        
        // 測試 2: 單例模式測試
        $results['singleton_pattern'] = self::test_singleton_pattern();
        
        // 測試 3: 區塊註冊測試
        $results['block_registration'] = self::test_block_registration();
        
        // 測試 4: Newebpay 設定檢查
        $results['newebpay_settings'] = self::test_newebpay_settings();
        
        // 測試 5: 資源檔案檢查
        $results['resource_files'] = self::test_resource_files();
        
        return $results;
    }
    
    /**
     * 測試類別載入
     */
    private static function test_class_loading() {
        if ( ! class_exists( 'Newebpay_Blocks' ) ) {
            return array(
                'status' => 'fail',
                'message' => 'Newebpay_Blocks 類別不存在'
            );
        }
        
        return array(
            'status' => 'pass',
            'message' => '類別載入成功'
        );
    }
    
    /**
     * 測試單例模式
     */
    private static function test_singleton_pattern() {
        if ( ! class_exists( 'Newebpay_Blocks' ) ) {
            return array(
                'status' => 'fail',
                'message' => 'Newebpay_Blocks 類別不存在'
            );
        }
        
        $instance1 = Newebpay_Blocks::get_instance();
        $instance2 = Newebpay_Blocks::get_instance();
        
        if ( $instance1 !== $instance2 ) {
            return array(
                'status' => 'fail',
                'message' => '單例模式失敗 - 創建了多個實例'
            );
        }
        
        return array(
            'status' => 'pass',
            'message' => '單例模式正常運作'
        );
    }
    
    /**
     * 測試區塊註冊
     */
    private static function test_block_registration() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return array(
                'status' => 'fail',
                'message' => 'Gutenberg 區塊不受支援'
            );
        }
        
        if ( ! class_exists( 'Newebpay_Blocks' ) ) {
            return array(
                'status' => 'fail',
                'message' => 'Newebpay_Blocks 類別不存在'
            );
        }
        
        // 檢查是否已註冊區塊
        $blocks = WP_Block_Type_Registry::get_instance();
        $registered_blocks = $blocks->get_all_registered();
        
        $newebpay_blocks = array();
        foreach ( $registered_blocks as $block_name => $block ) {
            if ( strpos( $block_name, 'newebpay/' ) === 0 ) {
                $newebpay_blocks[] = $block_name;
            }
        }
        
        if ( empty( $newebpay_blocks ) ) {
            return array(
                'status' => 'fail',
                'message' => '沒有發現已註冊的 Newebpay 區塊',
                'debug' => array(
                    'total_blocks' => count( $registered_blocks ),
                    'newebpay_blocks' => $newebpay_blocks
                )
            );
        }
        
        return array(
            'status' => 'pass',
            'message' => '找到 ' . count( $newebpay_blocks ) . ' 個已註冊的區塊',
            'blocks' => $newebpay_blocks
        );
    }
    
    /**
     * 測試 Newebpay 設定
     */
    private static function test_newebpay_settings() {
        if ( ! class_exists( 'Newebpay_Blocks' ) ) {
            return array(
                'status' => 'fail',
                'message' => 'Newebpay_Blocks 類別不存在'
            );
        }
        
        $instance = Newebpay_Blocks::get_instance();
        
        if ( ! method_exists( $instance, 'validate_newebpay_settings' ) ) {
            return array(
                'status' => 'fail',
                'message' => 'validate_newebpay_settings 方法不存在'
            );
        }
        
        $validation_result = $instance->validate_newebpay_settings();
        
        if ( ! is_array( $validation_result ) || ! isset( $validation_result['valid'] ) ) {
            return array(
                'status' => 'fail',
                'message' => '設定驗證方法回傳格式錯誤'
            );
        }
        
        if ( $validation_result['valid'] ) {
            return array(
                'status' => 'pass',
                'message' => $validation_result['message'],
                'enabled_methods' => isset( $validation_result['enabled_methods'] ) ? $validation_result['enabled_methods'] : array()
            );
        } else {
            return array(
                'status' => 'fail',
                'message' => $validation_result['message'],
                'errors' => isset( $validation_result['errors'] ) ? $validation_result['errors'] : array()
            );
        }
    }
    
    /**
     * 測試資源檔案
     */
    private static function test_resource_files() {
        $plugin_path = wp_normalize_path( NEWEB_MAIN_PATH );
        
        $required_files = array(
            'assets/js/blocks-editor.js' => 'JavaScript 編輯器檔案',
            'assets/js/blocks-frontend.js' => 'JavaScript 前端檔案', 
            'assets/css/blocks-editor.css' => 'CSS 編輯器檔案',
            'assets/css/blocks-frontend.css' => 'CSS 前端檔案'
        );
        
        $missing_files = array();
        $existing_files = array();
        
        foreach ( $required_files as $file => $description ) {
            $file_path = $plugin_path . '/' . $file;
            if ( file_exists( $file_path ) ) {
                $existing_files[] = $description;
            } else {
                $missing_files[] = $description;
            }
        }
        
        if ( ! empty( $missing_files ) ) {
            return array(
                'status' => 'fail',
                'message' => '缺少 ' . count( $missing_files ) . ' 個資源檔案',
                'missing' => $missing_files,
                'existing' => $existing_files
            );
        }
        
        return array(
            'status' => 'pass',
            'message' => '所有 ' . count( $existing_files ) . ' 個資源檔案都存在',
            'files' => $existing_files
        );
    }
    
    /**
     * 輸出測試結果
     */
    public static function display_test_results( $results ) {
        echo "Newebpay Blocks v1.0.10 測試結果\n";
        
        foreach ( $results as $test_name => $result ) {
            $status_icon = $result['status'] === 'pass' ? '✅' : '❌';
            $test_label = self::get_test_label( $test_name );
            $status_text = $result['status'] === 'pass' ? '通過' : '失敗';
            
            echo "{$test_label}: {$status_icon} {$status_text}\n";
            
            if ( isset( $result['message'] ) ) {
                echo "   {$result['message']}\n";
            }
        }
        
        // 檢查是否有失敗的測試
        $failed_tests = array_filter( $results, function( $result ) {
            return $result['status'] === 'fail';
        });
        
        if ( ! empty( $failed_tests ) ) {
            echo "⚠️ 有測試失敗，請檢查相關設定。\n";
        } else {
            echo "🎉 所有測試都通過了！\n";
        }
    }
    
    /**
     * 取得測試標籤
     */
    private static function get_test_label( $test_name ) {
        $labels = array(
            'class_loading' => '類別載入測試',
            'singleton_pattern' => '單例模式測試',
            'block_registration' => '區塊註冊測試',
            'newebpay_settings' => 'Newebpay 設定檢查',
            'resource_files' => '資源檔案檢查'
        );
        
        return isset( $labels[ $test_name ] ) ? $labels[ $test_name ] : ucfirst( $test_name );
    }
}

// 自動執行測試（僅在管理後台）
if ( is_admin() && current_user_can( 'manage_options' ) ) {
    add_action( 'wp_loaded', function() {
        if ( isset( $_GET['newebpay_test'] ) && $_GET['newebpay_test'] === '1' ) {
            $results = Newebpay_Blocks_Test_Runner::run_all_tests();
            
            header( 'Content-Type: text/plain; charset=utf-8' );
            Newebpay_Blocks_Test_Runner::display_test_results( $results );
            exit;
        }
    });
}
