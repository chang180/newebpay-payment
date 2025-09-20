<?php
/**
 * Newebpay Validator 單元測試
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

// 載入必要的檔案
require_once dirname(__FILE__) . '/../includes/class-newebpay-validator.php';
require_once dirname(__FILE__) . '/phpunit-stubs.php';

/**
 * 測試類別 - 繼承 WordPress 測試框架
 * 
 * @extends WP_UnitTestCase
 */
class Test_Newebpay_Validator extends WP_UnitTestCase {
    
    /**
     * 設置測試環境
     */
    public function setUp() {
        parent::setUp();
        
        // 載入必要的 WordPress 函數
        if (!function_exists('__')) {
            function __($text, $domain = 'default') {
                return $text;
            }
        }
        
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($str) {
                return trim(strip_tags($str));
            }
        }
        
        if (!function_exists('sanitize_email')) {
            function sanitize_email($email) {
                return filter_var($email, FILTER_SANITIZE_EMAIL);
            }
        }
    }
    
    /**
     * 測試付款資料驗證
     */
    public function test_validate_payment_data() {
        // 測試有效資料
        $valid_data = array(
            'MerchantID' => 'test_merchant',
            'Amt' => 100,
            'ItemDesc' => 'Test Product'
        );
        
        $result = Newebpay_Validator::validate_payment_data($valid_data);
        $this->assertEquals($valid_data['MerchantID'], $result['MerchantID']);
        $this->assertEquals($valid_data['Amt'], $result['Amt']);
    }
    
    /**
     * 測試無效的付款資料
     */
    public function test_validate_payment_data_invalid() {
        $this->expectException(InvalidArgumentException::class);
        
        $invalid_data = array(
            'MerchantID' => '',
            'Amt' => -100,
            'ItemDesc' => 'Test Product'
        );
        
        Newebpay_Validator::validate_payment_data($invalid_data);
    }
    
    /**
     * 測試金額驗證
     */
    public function test_validate_amount() {
        // 測試有效金額
        $valid_amount = Newebpay_Validator::validate_amount(100.50);
        $this->assertEquals(100.50, $valid_amount);
        
        // 測試無效金額
        $this->expectException(InvalidArgumentException::class);
        Newebpay_Validator::validate_amount(-100);
    }
    
    /**
     * 測試訂單 ID 驗證
     */
    public function test_validate_order_id() {
        // 測試有效訂單 ID
        $valid_id = Newebpay_Validator::validate_order_id(123);
        $this->assertEquals(123, $valid_id);
        
        // 測試無效訂單 ID
        $this->expectException(InvalidArgumentException::class);
        Newebpay_Validator::validate_order_id(0);
    }
    
    /**
     * 測試付款方式驗證
     */
    public function test_validate_payment_method() {
        $available_methods = array('credit', 'webatm', 'vacc');
        
        // 測試有效付款方式
        $valid_method = Newebpay_Validator::validate_payment_method('credit', $available_methods);
        $this->assertEquals('credit', $valid_method);
        
        // 測試無效付款方式
        $this->expectException(InvalidArgumentException::class);
        Newebpay_Validator::validate_payment_method('invalid_method', $available_methods);
    }
    
    /**
     * 測試回調資料驗證
     */
    public function test_validate_callback_data() {
        // 測試有效回調資料
        $valid_callback = array(
            'MerchantOrderNo' => '12345T1234567890',
            'Status' => 'SUCCESS',
            'Amt' => 100
        );
        
        $result = Newebpay_Validator::validate_callback_data($valid_callback);
        $this->assertEquals($valid_callback['MerchantOrderNo'], $result['MerchantOrderNo']);
        $this->assertEquals($valid_callback['Status'], $result['Status']);
    }
    
    /**
     * 測試無效的回調資料
     */
    public function test_validate_callback_data_invalid() {
        $this->expectException(InvalidArgumentException::class);
        
        $invalid_callback = array(
            'MerchantOrderNo' => '',
            'Status' => 'INVALID_STATUS'
        );
        
        Newebpay_Validator::validate_callback_data($invalid_callback);
    }
    
    /**
     * 測試金額邊界值
     */
    public function test_validate_amount_boundaries() {
        // 測試最小有效金額
        $min_amount = Newebpay_Validator::validate_amount(0.01);
        $this->assertEquals(0.01, $min_amount);
        
        // 測試最大有效金額
        $max_amount = Newebpay_Validator::validate_amount(999999);
        $this->assertEquals(999999, $max_amount);
        
        // 測試超出範圍的金額
        $this->expectException(InvalidArgumentException::class);
        Newebpay_Validator::validate_amount(1000000);
    }
    
    /**
     * 測試商品描述長度限制
     */
    public function test_item_description_length_limit() {
        $long_description = str_repeat('測試商品', 20); // 超過50字元
        
        $data = array(
            'MerchantID' => 'test_merchant',
            'Amt' => 100,
            'ItemDesc' => $long_description
        );
        
        $result = Newebpay_Validator::validate_payment_data($data);
        $this->assertLessThanOrEqual(50, mb_strlen($result['ItemDesc']));
    }
    
    /**
     * 測試資料清理功能
     */
    public function test_sanitize_payment_data() {
        $dirty_data = array(
            'MerchantID' => '  test_merchant  ',
            'Amt' => '100.50',
            'ItemDesc' => '<script>alert("test")</script>Test Product',
            'Email' => 'test@example.com'
        );
        
        $result = Newebpay_Validator::validate_payment_data($dirty_data);
        
        // 檢查資料是否被正確清理
        $this->assertEquals('test_merchant', $result['MerchantID']);
        $this->assertEquals(100.50, $result['Amt']);
        $this->assertStringNotContainsString('<script>', $result['ItemDesc']);
        $this->assertEquals('test@example.com', $result['Email']);
    }
    
    /**
     * 測試空值處理
     */
    public function test_empty_values_handling() {
        $data_with_empty = array(
            'MerchantID' => 'test_merchant',
            'Amt' => 100,
            'ItemDesc' => 'Test Product',
            'Email' => '',
            'Phone' => null
        );
        
        $result = Newebpay_Validator::validate_payment_data($data_with_empty);
        
        // 空值應該被移除
        $this->assertArrayNotHasKey('Email', $result);
        $this->assertArrayNotHasKey('Phone', $result);
    }
    
    /**
     * 測試異常狀態值
     */
    public function test_invalid_status_values() {
        $invalid_statuses = array('PENDING', 'PROCESSING', 'UNKNOWN');
        
        foreach ($invalid_statuses as $status) {
            $this->expectException(InvalidArgumentException::class);
            
            $callback_data = array(
                'MerchantOrderNo' => '12345T1234567890',
                'Status' => $status
            );
            
            Newebpay_Validator::validate_callback_data($callback_data);
        }
    }
    
    /**
     * 測試有效狀態值
     */
    public function test_valid_status_values() {
        $valid_statuses = array('SUCCESS', 'CUSTOM', 'FAIL');
        
        foreach ($valid_statuses as $status) {
            $callback_data = array(
                'MerchantOrderNo' => '12345T1234567890',
                'Status' => $status
            );
            
            $result = Newebpay_Validator::validate_callback_data($callback_data);
            $this->assertEquals($status, $result['Status']);
        }
    }
}
