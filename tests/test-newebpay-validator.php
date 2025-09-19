<?php
/**
 * Newebpay Validator 單元測試
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

class Test_Newebpay_Validator extends WP_UnitTestCase {
    
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
}
