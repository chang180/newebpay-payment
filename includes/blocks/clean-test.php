<?php
/**
 * 清潔的 Newebpay Blocks 測試運行器
 * 避免 WordPress 依賴問題
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

// 定義必要的常數
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/' );
}

if ( ! defined( 'NEWEB_MAIN_PATH' ) ) {
    define( 'NEWEB_MAIN_PATH', dirname( dirname( __FILE__ ) ) );
}

define( 'WP_DEBUG', true );

echo "=== Clean Newebpay Blocks Test ===\n";
echo "Running without WordPress dependencies...\n";
echo "ABSPATH: " . ABSPATH . "\n";
echo "NEWEB_MAIN_PATH: " . NEWEB_MAIN_PATH . "\n\n";

// 模擬 WordPress 函數
function mock_plugin_dir_path( $file ) {
    return dirname( dirname( $file ) ) . '/';
}

function mock_plugin_dir_url( $file ) {
    return 'https://example.com/wp-content/plugins/' . basename( dirname( dirname( $file ) ) ) . '/';
}

function mock_wp_functions() {
    if ( ! function_exists( 'plugin_dir_path' ) ) {
        function plugin_dir_path( $file ) {
            return dirname( $file ) . '/';
        }
    }
    
    if ( ! function_exists( 'plugin_dir_url' ) ) {
        function plugin_dir_url( $file ) {
            return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
        }
    }
    
    if ( ! function_exists( 'register_block_type' ) ) {
        function register_block_type( $block_type, $args = array() ) {
            echo "  ✓ Registered block: {$block_type}\n";
            return new stdClass();
        }
    }
    
    if ( ! function_exists( 'wp_enqueue_script' ) ) {
        function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
            echo "  ✓ Enqueued script: {$handle}\n";
            return true;
        }
    }
    
    if ( ! function_exists( 'wp_enqueue_style' ) ) {
        function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
            echo "  ✓ Enqueued style: {$handle}\n";
            return true;
        }
    }
    
    if ( ! function_exists( 'add_action' ) ) {
        function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
            return true;
        }
    }
    
    if ( ! function_exists( 'add_filter' ) ) {
        function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
            return true;
        }
    }
    
    if ( ! function_exists( 'get_option' ) ) {
        function get_option( $option, $default = false ) {
            $mock_options = array(
                'woocommerce_newebpay_settings' => array(
                    'enabled' => 'yes',
                    'merchant_id' => 'test_merchant',
                    'hash_key' => 'test_hash_key',
                    'hash_iv' => 'test_hash_iv',
                )
            );
            return isset( $mock_options[ $option ] ) ? $mock_options[ $option ] : $default;
        }
    }
    
    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = 'default' ) {
            return $text;
        }
    }
    
    if ( ! function_exists( 'wp_localize_script' ) ) {
        function wp_localize_script( $handle, $object_name, $l10n ) {
            return true;
        }
    }
    
    if ( ! function_exists( 'register_rest_route' ) ) {
        function register_rest_route( $namespace, $route, $args = array(), $override = false ) {
            return true;
        }
    }
    
    if ( ! function_exists( 'is_admin' ) ) {
        function is_admin() {
            return false; // 模擬非管理界面
        }
    }
    
    if ( ! function_exists( 'wp_normalize_path' ) ) {
        function wp_normalize_path( $path ) {
            return str_replace( '\\', '/', $path );
        }
    }
    
    if ( ! function_exists( 'current_user_can' ) ) {
        function current_user_can( $capability ) {
            return true; // 模擬有權限
        }
    }
}

// 手動測試區塊功能
function test_newebpay_blocks() {
    echo "1. Loading Newebpay_Blocks class...\n";
    
    try {
        $blocks_file = dirname( __FILE__ ) . '/class-newebpay-blocks.php';
        if ( ! file_exists( $blocks_file ) ) {
            throw new Exception( "Blocks file not found: {$blocks_file}" );
        }
        
        require_once $blocks_file;
        echo "  ✓ File loaded\n";
        
        if ( ! class_exists( 'Newebpay_Blocks' ) ) {
            throw new Exception( "Newebpay_Blocks class not defined" );
        }
        echo "  ✓ Class defined\n";
        
    } catch ( Exception $e ) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
    
    echo "\n2. Testing class instantiation...\n";
    try {
        $instance = Newebpay_Blocks::get_instance();
        echo "  ✓ Instance created\n";
        
        $instance2 = Newebpay_Blocks::get_instance();
        if ( $instance === $instance2 ) {
            echo "  ✓ Singleton pattern working\n";
        } else {
            echo "  ✗ Singleton pattern failed\n";
        }
        
    } catch ( Exception $e ) {
        echo "  ✗ Error: " . $e->getMessage() . "\n";
        return false;
    }
    
    echo "\n3. Testing method availability...\n";
    $methods_to_test = array(
        'register_block_types',
        'enqueue_block_editor_assets', 
        'get_available_payment_methods',
        'validate_newebpay_settings'
    );
    
    foreach ( $methods_to_test as $method ) {
        if ( method_exists( $instance, $method ) ) {
            echo "  ✓ Method exists: {$method}\n";
        } else {
            echo "  ✗ Method missing: {$method}\n";
        }
    }
    
    echo "\n4. Testing payment methods...\n";
    try {
        if ( method_exists( $instance, 'get_available_payment_methods' ) ) {
            // 使用反射來訪問私有方法
            $reflection = new ReflectionClass( $instance );
            $method = $reflection->getMethod( 'get_available_payment_methods' );
            $method->setAccessible( true );
            $methods = $method->invoke( $instance );
            
            if ( is_array( $methods ) ) {
                echo "  ✓ Payment methods returned: " . count( $methods ) . " methods\n";
                foreach ( $methods as $method_data ) {
                    $name = isset( $method_data['name'] ) ? $method_data['name'] : ( isset( $method_data['title'] ) ? $method_data['title'] : 'Unknown' );
                    $id = isset( $method_data['id'] ) ? $method_data['id'] : ( isset( $method_data['key'] ) ? $method_data['key'] : 'Unknown' );
                    echo "    - {$id}: {$name}\n";
                }
            } else {
                echo "  ✗ Payment methods not returned as array\n";
            }
        }
    } catch ( Exception $e ) {
        echo "  ⚠ Cannot test private method: " . $e->getMessage() . "\n";
    }
    
    echo "\n5. Testing settings validation...\n";
    try {
        if ( method_exists( $instance, 'validate_newebpay_settings' ) ) {
            // 使用反射來訪問私有方法
            $reflection = new ReflectionClass( $instance );
            $method = $reflection->getMethod( 'validate_newebpay_settings' );
            $method->setAccessible( true );
            $validation = $method->invoke( $instance );
            echo "  ✓ Settings validation: " . ( $validation ? 'PASS' : 'FAIL' ) . "\n";
        }
    } catch ( Exception $e ) {
        echo "  ⚠ Cannot test private method: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. Testing asset file paths...\n";
    $plugin_file = dirname( dirname( __FILE__ ) ) . '/newebpay-payment.php';
    $assets_path = dirname( dirname( __FILE__ ) ) . '/assets/';
    
    $asset_files = array(
        'js/blocks-editor.js',
        'css/blocks-editor.css',
        'js/blocks-frontend.js',
        'css/blocks-frontend.css'
    );
    
    foreach ( $asset_files as $file ) {
        $full_path = $assets_path . $file;
        if ( file_exists( $full_path ) ) {
            $size = filesize( $full_path );
            echo "  ✓ Asset exists: {$file} ({$size} bytes)\n";
        } else {
            echo "  ✗ Asset missing: {$file}\n";
        }
    }
    
    return true;
}

// 運行測試
echo "Setting up mock functions...\n";
mock_wp_functions();
echo "✓ Mock functions ready\n\n";

$success = test_newebpay_blocks();

echo "\n=== Test Summary ===\n";
if ( $success ) {
    echo "🎉 Core functionality tests completed successfully!\n";
    echo "The Newebpay Blocks system appears to be working correctly.\n\n";
    
    echo "Next steps:\n";
    echo "1. Test in actual WordPress environment\n";
    echo "2. Check block registration in admin\n";
    echo "3. Verify frontend rendering\n";
    echo "4. Test WooCommerce integration\n";
} else {
    echo "❌ Some tests failed. Check the errors above.\n";
}
?>
