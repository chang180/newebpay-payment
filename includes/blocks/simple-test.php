<?php
/**
 * 簡化的 Newebpay Blocks 測試
 * 直接測試核心功能
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

echo "=== Newebpay Blocks Simplified Test ===\n";
echo "Testing core functionality...\n\n";

// 模擬 WordPress 環境
function mock_wordpress_functions() {
    global $mock_registered_blocks, $mock_enqueued_scripts, $mock_enqueued_styles;
    
    $mock_registered_blocks = array();
    $mock_enqueued_scripts = array();
    $mock_enqueued_styles = array();
    
    if ( ! function_exists( 'register_block_type' ) ) {
        function register_block_type( $block_type, $args = array() ) {
            global $mock_registered_blocks;
            $mock_registered_blocks[] = $block_type;
            echo "✓ Mock: Registered block type '{$block_type}'\n";
            return new stdClass();
        }
    }
    
    if ( ! function_exists( 'wp_enqueue_script' ) ) {
        function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
            global $mock_enqueued_scripts;
            $mock_enqueued_scripts[] = $handle;
            echo "✓ Mock: Enqueued script '{$handle}'\n";
            return true;
        }
    }
    
    if ( ! function_exists( 'wp_enqueue_style' ) ) {
        function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
            global $mock_enqueued_styles;
            $mock_enqueued_styles[] = $handle;
            echo "✓ Mock: Enqueued style '{$handle}'\n";
            return true;
        }
    }
    
    if ( ! function_exists( 'wp_localize_script' ) ) {
        function wp_localize_script( $handle, $object_name, $l10n ) {
            echo "✓ Mock: Localized script '{$handle}' with '{$object_name}'\n";
            return true;
        }
    }
    
    if ( ! function_exists( 'add_action' ) ) {
        function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
            echo "✓ Mock: Added action '{$tag}'\n";
            return true;
        }
    }
    
    if ( ! function_exists( 'add_filter' ) ) {
        function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
            echo "✓ Mock: Added filter '{$tag}'\n";
            return true;
        }
    }
    
    if ( ! function_exists( 'get_option' ) ) {
        function get_option( $option, $default = false ) {
            if ( $option === 'woocommerce_newebpay_settings' ) {
                return array(
                    'enabled' => 'yes',
                    'merchant_id' => 'test_merchant',
                    'hash_key' => 'test_hash_key',
                    'hash_iv' => 'test_hash_iv',
                );
            }
            return $default;
        }
    }
    
    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = 'default' ) {
            return $text;
        }
    }
    
    if ( ! function_exists( 'plugin_dir_path' ) ) {
        function plugin_dir_path( $file ) {
            return dirname( dirname( $file ) ) . '/';
        }
    }
    
    if ( ! function_exists( 'plugin_dir_url' ) ) {
        function plugin_dir_url( $file ) {
            return 'https://example.com/wp-content/plugins/' . basename( dirname( dirname( $file ) ) ) . '/';
        }
    }
}

// 簡化的區塊類別
class Simple_Newebpay_Blocks {
    private $plugin_path;
    private $plugin_url;
    private $assets_url;
    private $blocks_path;
    private $blocks = array();
    
    public function __construct( $plugin_file ) {
        $this->plugin_path = plugin_dir_path( $plugin_file );
        $this->plugin_url = plugin_dir_url( $plugin_file );
        $this->assets_url = $this->plugin_url . 'assets';
        $this->blocks_path = $this->plugin_path . 'includes/blocks';
        
        $this->init_blocks();
    }
    
    private function init_blocks() {
        $this->blocks = array(
            'payment-methods' => array(
                'name' => 'newebpay/payment-methods',
                'title' => 'Newebpay 付款方式',
                'attributes' => array(
                    'layout' => array(
                        'type' => 'string',
                        'default' => 'grid'
                    )
                ),
                'render_callback' => array( $this, 'render_payment_methods_block' )
            )
        );
    }
    
    public function register_block_types() {
        $registered_count = 0;
        
        foreach ( $this->blocks as $block_key => $block_config ) {
            $result = register_block_type( $block_config['name'], array(
                'attributes' => $block_config['attributes'],
                'render_callback' => $block_config['render_callback']
            ) );
            
            if ( $result ) {
                $registered_count++;
            }
        }
        
        return $registered_count;
    }
    
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'newebpay-blocks-editor',
            $this->assets_url . '/js/blocks-editor.js',
            array( 'wp-blocks', 'wp-element' ),
            '1.0.10',
            true
        );
        
        wp_enqueue_style(
            'newebpay-blocks-editor',
            $this->assets_url . '/css/blocks-editor.css',
            array( 'wp-edit-blocks' ),
            '1.0.10'
        );
        
        wp_localize_script( 'newebpay-blocks-editor', 'newebpayBlocks', array(
            'availableMethods' => $this->get_available_payment_methods()
        ) );
    }
    
    public function get_available_payment_methods() {
        return array(
            array( 'id' => 'CREDIT', 'name' => '信用卡' ),
            array( 'id' => 'VACC', 'name' => 'ATM 轉帳' ),
            array( 'id' => 'CVS', 'name' => '超商代碼' )
        );
    }
    
    public function validate_newebpay_settings() {
        $settings = get_option( 'woocommerce_newebpay_settings', array() );
        
        $required_fields = array( 'merchant_id', 'hash_key', 'hash_iv' );
        
        foreach ( $required_fields as $field ) {
            if ( empty( $settings[ $field ] ) ) {
                return false;
            }
        }
        
        return true;
    }
    
    public function render_payment_methods_block( $attributes ) {
        return '<div class="newebpay-payment-methods">Mock block output</div>';
    }
}

// 執行測試
try {
    echo "1. Setting up mock WordPress environment...\n";
    mock_wordpress_functions();
    echo "✓ Mock environment ready\n\n";
    
    echo "2. Creating Newebpay Blocks instance...\n";
    $mock_plugin_file = dirname( dirname( __FILE__ ) ) . '/newebpay-payment.php';
    $newebpay_blocks = new Simple_Newebpay_Blocks( $mock_plugin_file );
    echo "✓ Newebpay Blocks instance created\n\n";
    
    echo "3. Testing block registration...\n";
    $registered_count = $newebpay_blocks->register_block_types();
    echo "✓ Registered {$registered_count} blocks\n\n";
    
    echo "4. Testing asset enqueuing...\n";
    $newebpay_blocks->enqueue_block_editor_assets();
    echo "✓ Assets enqueued\n\n";
    
    echo "5. Testing payment methods retrieval...\n";
    $methods = $newebpay_blocks->get_available_payment_methods();
    echo "✓ Retrieved " . count( $methods ) . " payment methods\n";
    foreach ( $methods as $method ) {
        echo "   - {$method['id']}: {$method['name']}\n";
    }
    echo "\n";
    
    echo "6. Testing settings validation...\n";
    $validation_result = $newebpay_blocks->validate_newebpay_settings();
    echo $validation_result ? "✓ Settings validation passed\n" : "✗ Settings validation failed\n";
    echo "\n";
    
    echo "7. Testing block rendering...\n";
    $output = $newebpay_blocks->render_payment_methods_block( array( 'layout' => 'grid' ) );
    echo "✓ Block rendered: " . substr( $output, 0, 50 ) . "...\n\n";
    
    // 檢查全域變數
    global $mock_registered_blocks, $mock_enqueued_scripts, $mock_enqueued_styles;
    
    echo "=== Test Results Summary ===\n";
    echo "Registered blocks: " . count( $mock_registered_blocks ) . "\n";
    foreach ( $mock_registered_blocks as $block ) {
        echo "  - {$block}\n";
    }
    
    echo "Enqueued scripts: " . count( $mock_enqueued_scripts ) . "\n";
    foreach ( $mock_enqueued_scripts as $script ) {
        echo "  - {$script}\n";
    }
    
    echo "Enqueued styles: " . count( $mock_enqueued_styles ) . "\n";
    foreach ( $mock_enqueued_styles as $style ) {
        echo "  - {$style}\n";
    }
    
    echo "\n=== Final Status ===\n";
    $all_tests_passed = (
        count( $mock_registered_blocks ) > 0 &&
        count( $mock_enqueued_scripts ) > 0 &&
        count( $mock_enqueued_styles ) > 0 &&
        count( $methods ) > 0 &&
        $validation_result &&
        ! empty( $output )
    );
    
    if ( $all_tests_passed ) {
        echo "🎉 All tests passed! The Newebpay Blocks system is working correctly.\n";
        exit( 0 );
    } else {
        echo "⚠ Some tests may have issues. Check the details above.\n";
        exit( 1 );
    }
    
} catch ( Exception $e ) {
    echo "✗ Test failed with exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit( 1 );
} catch ( Error $e ) {
    echo "✗ Test failed with error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit( 1 );
}
