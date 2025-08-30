<?php
/**
 * WordPress 環境中的 Newebpay Blocks 測試
 * 
 * 訪問：http://your-wordpress-site/wp-content/plugins/newebpay-payment/includes/blocks/wordpress-test.php
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

// 載入 WordPress 環境
$wp_path = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/';
if ( file_exists( $wp_path . 'wp-load.php' ) ) {
    require_once $wp_path . 'wp-load.php';
} else {
    die( 'WordPress not found. Please make sure this file is in the correct location.' );
}

// 設定除錯模式
if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', true );
}

echo '<html><head><title>Newebpay Blocks Test</title>';
echo '<style>body{font-family:monospace;margin:20px;} .pass{color:green;} .fail{color:red;} .info{color:blue;}</style>';
echo '</head><body>';

echo '<h1>Newebpay Blocks WordPress Environment Test</h1>';
echo '<p class="info">Testing in actual WordPress environment...</p>';

// 測試函數
function test_result( $test_name, $status, $message = '' ) {
    $class = $status ? 'pass' : 'fail';
    $symbol = $status ? '✓' : '✗';
    echo "<p class='{$class}'>{$symbol} {$test_name}";
    if ( $message ) {
        echo " - {$message}";
    }
    echo "</p>";
    return $status;
}

$results = array();

echo '<h2>1. WordPress Environment Check</h2>';
$results['wp_version'] = test_result( 'WordPress Version', ! empty( $wp_version ), get_bloginfo( 'version' ) );
$results['wp_debug'] = test_result( 'WP_DEBUG Enabled', defined( 'WP_DEBUG' ) && WP_DEBUG, 'Debug mode: ' . ( WP_DEBUG ? 'ON' : 'OFF' ) );

echo '<h2>2. Plugin File Check</h2>';
$plugin_file = dirname( dirname( __FILE__ ) ) . '/newebpay-payment.php';
$results['plugin_file'] = test_result( 'Main Plugin File', file_exists( $plugin_file ), $plugin_file );

$blocks_file = dirname( __FILE__ ) . '/class-newebpay-blocks.php';
$results['blocks_file'] = test_result( 'Blocks Class File', file_exists( $blocks_file ), $blocks_file );

echo '<h2>3. Asset Files Check</h2>';
$assets_path = dirname( dirname( __FILE__ ) ) . '/assets/';
$editor_js = $assets_path . 'js/blocks-editor.js';
$editor_css = $assets_path . 'css/blocks-editor.css';
$frontend_js = $assets_path . 'js/blocks-frontend.js';
$frontend_css = $assets_path . 'css/blocks-frontend.css';

$results['editor_js'] = test_result( 'Editor JS', file_exists( $editor_js ), filesize( $editor_js ) . ' bytes' );
$results['editor_css'] = test_result( 'Editor CSS', file_exists( $editor_css ), filesize( $editor_css ) . ' bytes' );
$results['frontend_js'] = test_result( 'Frontend JS', file_exists( $frontend_js ), filesize( $frontend_js ) . ' bytes' );
$results['frontend_css'] = test_result( 'Frontend CSS', file_exists( $frontend_css ), filesize( $frontend_css ) . ' bytes' );

echo '<h2>4. WordPress Functions Check</h2>';
$results['wp_functions'] = test_result( 'Core WP Functions', function_exists( 'register_block_type' ) && function_exists( 'wp_enqueue_script' ), 'Block API available' );
$results['woocommerce'] = test_result( 'WooCommerce Active', class_exists( 'WooCommerce' ), 'WooCommerce: ' . ( class_exists( 'WooCommerce' ) ? 'Active' : 'Not Active' ) );

echo '<h2>5. Newebpay Blocks Class Test</h2>';
try {
    if ( ! class_exists( 'Newebpay_Blocks' ) ) {
        require_once $blocks_file;
    }
    
    $results['class_loaded'] = test_result( 'Class Loading', class_exists( 'Newebpay_Blocks' ), 'Class definition found' );
    
    if ( class_exists( 'Newebpay_Blocks' ) ) {
        // 測試單例模式
        $instance1 = Newebpay_Blocks::get_instance();
        $instance2 = Newebpay_Blocks::get_instance();
        $results['singleton'] = test_result( 'Singleton Pattern', $instance1 === $instance2, 'Same instance returned' );
        
        // 測試方法存在
        $methods_to_check = array( 'register_block_types', 'enqueue_block_editor_assets', 'get_available_payment_methods', 'validate_newebpay_settings' );
        foreach ( $methods_to_check as $method ) {
            $results["method_{$method}"] = test_result( "Method: {$method}", method_exists( $instance1, $method ), 'Method exists' );
        }
        
        // 測試付款方式獲取
        if ( method_exists( $instance1, 'get_available_payment_methods' ) ) {
            $methods = $instance1->get_available_payment_methods();
            $results['payment_methods'] = test_result( 'Payment Methods', is_array( $methods ), count( $methods ) . ' methods found' );
        }
        
        // 測試設定驗證
        if ( method_exists( $instance1, 'validate_newebpay_settings' ) ) {
            $validation = $instance1->validate_newebpay_settings();
            $results['settings_validation'] = test_result( 'Settings Validation', is_bool( $validation ), 'Validation: ' . ( $validation ? 'PASS' : 'FAIL' ) );
        }
    }
    
} catch ( Exception $e ) {
    $results['class_error'] = test_result( 'Class Error', false, $e->getMessage() );
} catch ( Error $e ) {
    $results['class_fatal'] = test_result( 'Class Fatal Error', false, $e->getMessage() );
}

echo '<h2>6. WordPress Options Check</h2>';
$newebpay_settings = get_option( 'woocommerce_newebpay_settings', array() );
$results['settings_exist'] = test_result( 'Newebpay Settings', ! empty( $newebpay_settings ), count( $newebpay_settings ) . ' settings found' );

if ( ! empty( $newebpay_settings ) ) {
    $required_fields = array( 'enabled', 'merchant_id', 'hash_key', 'hash_iv' );
    foreach ( $required_fields as $field ) {
        $exists = isset( $newebpay_settings[ $field ] ) && ! empty( $newebpay_settings[ $field ] );
        $results["setting_{$field}"] = test_result( "Setting: {$field}", $exists, $exists ? 'Set' : 'Missing' );
    }
}

echo '<h2>7. Block Registration Test</h2>';
if ( function_exists( 'register_block_type' ) && class_exists( 'Newebpay_Blocks' ) ) {
    try {
        $instance = Newebpay_Blocks::get_instance();
        if ( method_exists( $instance, 'register_block_types' ) ) {
            // 模擬註冊過程
            $reflection = new ReflectionClass( $instance );
            $blocks_property = $reflection->getProperty( 'blocks' );
            $blocks_property->setAccessible( true );
            $blocks = $blocks_property->getValue( $instance );
            
            $results['blocks_config'] = test_result( 'Blocks Configuration', ! empty( $blocks ), count( $blocks ) . ' blocks configured' );
            
            foreach ( $blocks as $block_key => $block_config ) {
                $results["block_{$block_key}"] = test_result( "Block: {$block_key}", isset( $block_config['name'] ), $block_config['name'] ?? 'No name' );
            }
        }
    } catch ( Exception $e ) {
        $results['block_registration_error'] = test_result( 'Block Registration Error', false, $e->getMessage() );
    }
}

// 計算總結果
$total_tests = count( $results );
$passed_tests = count( array_filter( $results ) );
$pass_rate = $total_tests > 0 ? ( $passed_tests / $total_tests ) * 100 : 0;

echo '<h2>Summary</h2>';
echo "<p><strong>Total Tests:</strong> {$total_tests}</p>";
echo "<p><strong>Passed:</strong> <span class='pass'>{$passed_tests}</span></p>";
echo "<p><strong>Failed:</strong> <span class='fail'>" . ( $total_tests - $passed_tests ) . "</span></p>";
echo "<p><strong>Pass Rate:</strong> " . number_format( $pass_rate, 1 ) . "%</p>";

if ( $pass_rate >= 80 ) {
    echo '<p class="pass"><strong>🎉 Tests mostly successful!</strong></p>';
} elseif ( $pass_rate >= 60 ) {
    echo '<p class="info"><strong>⚠ Tests partially successful. Some issues detected.</strong></p>';
} else {
    echo '<p class="fail"><strong>❌ Multiple test failures detected.</strong></p>';
}

echo '<h2>Debug Information</h2>';
echo '<pre>';
echo "WordPress Version: " . get_bloginfo( 'version' ) . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Plugin Path: " . plugin_dir_path( $plugin_file ) . "\n";
echo "Plugin URL: " . plugin_dir_url( $plugin_file ) . "\n";
echo "Current User: " . ( is_user_logged_in() ? wp_get_current_user()->user_login : 'Not logged in' ) . "\n";
echo "Memory Limit: " . ini_get( 'memory_limit' ) . "\n";
echo '</pre>';

echo '</body></html>';
?>
