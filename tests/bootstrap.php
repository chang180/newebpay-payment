<?php
/**
 * WordPress 測試環境 Bootstrap
 * 
 * @package NeWebPay_Payment
 * @version 1.1.0
 */

// 設定測試環境
define('WP_TESTS_DOMAIN', 'localhost');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');

// 載入 WordPress 測試框架
if (getenv('WP_TESTS_DIR')) {
    $wp_tests_dir = getenv('WP_TESTS_DIR');
} else {
    $wp_tests_dir = '/tmp/wordpress-tests-lib';
}

// 檢查 WordPress 測試框架是否存在
if (!file_exists($wp_tests_dir . '/includes/functions.php')) {
    echo "WordPress 測試框架未找到。請設置 WP_TESTS_DIR 環境變數或安裝 WordPress 測試框架。\n";
    exit(1);
}

// 載入 WordPress 測試框架
require_once $wp_tests_dir . '/includes/functions.php';

// 載入插件
function _manually_load_plugin() {
    require dirname(__FILE__) . '/../Central.php';
}

// 如果 WordPress 測試框架可用，使用其過濾器
if (function_exists('tests_add_filter')) {
    tests_add_filter('muplugins_loaded', '_manually_load_plugin');
} else {
    // 直接載入插件
    _manually_load_plugin();
}

// 提供 tests_add_filter 函數的備用實現
if (!function_exists('tests_add_filter')) {
    /**
     * 測試過濾器函數的備用實現
     * 
     * @param string $hook 鉤子名稱
     * @param callable $callback 回調函數
     */
    function tests_add_filter($hook, $callback) {
        // 在測試環境中直接執行回調
        if ($hook === 'muplugins_loaded') {
            $callback();
        }
    }
}

// 啟動測試環境
require $wp_tests_dir . '/includes/bootstrap.php';

// 載入必要的測試工具
if (class_exists('WP_UnitTestCase')) {
    // WordPress 測試框架已載入
    echo "WordPress 測試框架載入成功。\n";
} else {
    echo "WordPress 測試框架載入失敗。\n";
    exit(1);
}
