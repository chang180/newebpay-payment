<?php
/**
 * Newebpay Blocks Test Runner
 * 獨立測試運行器，用於調試區塊功能
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

// 定義必要的常數
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/' );
}

// 設定除錯模式
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );

echo "=== Newebpay Blocks Test Runner ===\n";
echo "Initializing test environment...\n";
echo "ABSPATH: " . ABSPATH . "\n\n";

// 模擬 WordPress 函數
if ( ! function_exists( 'wp_create_nonce' ) ) {
    function wp_create_nonce( $action = -1 ) {
        return 'test_nonce_' . md5( $action );
    }
}

if ( ! function_exists( 'rest_url' ) ) {
    function rest_url( $path = '' ) {
        return 'https://example.com/wp-json/' . ltrim( $path, '/' );
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        // 模擬一些基本設定
        $mock_options = array(
            'woocommerce_newebpay_settings' => array(
                'enabled' => 'yes',
                'merchant_id' => 'test_merchant',
                'hash_key' => 'test_hash_key',
                'hash_iv' => 'test_hash_iv',
                'payment_methods' => array( 'CREDIT', 'VACC', 'CVS' )
            )
        );
        
        return isset( $mock_options[ $option ] ) ? $mock_options[ $option ] : $default;
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return dirname( $file ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'https://example.com/wp-content/plugins/' . basename( dirname( dirname( $file ) ) ) . '/';
    }
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
    function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
        echo "Mock: Enqueuing script '{$handle}' from '{$src}'\n";
        return true;
    }
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
    function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
        echo "Mock: Enqueuing style '{$handle}' from '{$src}'\n";
        return true;
    }
}

if ( ! function_exists( 'wp_localize_script' ) ) {
    function wp_localize_script( $handle, $object_name, $l10n ) {
        echo "Mock: Localizing script '{$handle}' with object '{$object_name}'\n";
        return true;
    }
}

if ( ! function_exists( 'register_block_type' ) ) {
    function register_block_type( $block_type, $args = array() ) {
        echo "Mock: Registering block type '{$block_type}'\n";
        return new stdClass(); // 返回模擬的區塊物件
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        echo "Mock: Adding action '{$tag}'\n";
        return true;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
        echo "Mock: Adding filter '{$tag}'\n";
        return true;
    }
}

if ( ! function_exists( 'register_rest_route' ) ) {
    function register_rest_route( $namespace, $route, $args = array(), $override = false ) {
        echo "Mock: Registering REST route '{$namespace}{$route}'\n";
        return true;
    }
}

try {
    // 載入主要類別
    echo "Loading Newebpay_Blocks class...\n";
    require_once dirname( __FILE__ ) . '/class-newebpay-blocks.php';
    echo "✓ Newebpay_Blocks class loaded successfully\n";
    
    // 載入測試類別
    echo "Loading test class...\n";
    require_once dirname( __FILE__ ) . '/class-newebpay-blocks-test.php';
    echo "✓ Test class loaded successfully\n\n";
    
    // 創建測試實例
    echo "Initializing test runner...\n";
    if ( ! class_exists( 'Newebpay_Blocks_Test_Runner' ) ) {
        throw new Exception( 'Newebpay_Blocks_Test_Runner class not found' );
    }
    echo "✓ Test runner class found\n\n";
    
    // 運行所有測試
    echo "=== Running All Tests ===\n";
    $results = Newebpay_Blocks_Test_Runner::run_all_tests();
    
    echo "\n=== Test Results Summary ===\n";
    foreach ( $results as $test_name => $result ) {
        $status = $result['status'] ? '✓ PASS' : '✗ FAIL';
        echo sprintf( "%-30s: %s\n", $test_name, $status );
        
        if ( ! $result['status'] && ! empty( $result['message'] ) ) {
            echo "   Error: " . $result['message'] . "\n";
        }
        
        if ( ! empty( $result['details'] ) ) {
            echo "   Details: " . $result['details'] . "\n";
        }
    }
    
    // 計算通過率
    $total_tests = count( $results );
    $passed_tests = count( array_filter( $results, function( $result ) {
        return $result['status'];
    } ) );
    
    $pass_rate = $total_tests > 0 ? ( $passed_tests / $total_tests ) * 100 : 0;
    
    echo "\n=== Summary ===\n";
    echo "Total Tests: {$total_tests}\n";
    echo "Passed: {$passed_tests}\n";
    echo "Failed: " . ( $total_tests - $passed_tests ) . "\n";
    echo "Pass Rate: " . number_format( $pass_rate, 1 ) . "%\n\n";
    
    if ( $pass_rate < 100 ) {
        echo "⚠ Some tests failed. Check the details above.\n";
        exit( 1 );
    } else {
        echo "🎉 All tests passed!\n";
        exit( 0 );
    }
    
} catch ( Exception $e ) {
    echo "✗ Test runner failed: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit( 1 );
} catch ( Error $e ) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit( 1 );
}
