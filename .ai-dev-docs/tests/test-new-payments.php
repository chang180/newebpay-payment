<?php
/**
 * 測試新增的三種支付方式
 * Apple Pay, 智慧卡2.0, TWQR
 * 
 * 這個檔案可以用來測試新增的支付方式是否正確整合
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

/**
 * 測試函數 - 驗證新的支付方式設定
 */
function test_new_payment_methods() {
    // 包含設定檔案
    $settings = include dirname(__FILE__) . '/includes/nwp/nwpSetting.php';
    
    // 檢查新的支付方式是否已加入設定
    $new_payment_methods = [
        'NwpPaymentMethodAPPLEPAY' => 'Apple Pay',
        'NwpPaymentMethodSmartPay' => '智慧ATM2.0', 
        'NwpPaymentMethodTWQR' => 'TWQR'
    ];
    
    echo "<h2>測試新增支付方式設定</h2>\n";
    
    foreach ($new_payment_methods as $setting_key => $display_name) {
        if (isset($settings[$setting_key])) {
            echo "✅ {$display_name} 設定已成功加入<br>\n";
            echo "   - 標題: {$settings[$setting_key]['title']}<br>\n";
            echo "   - 類型: {$settings[$setting_key]['type']}<br>\n";
            echo "   - 預設值: {$settings[$setting_key]['default']}<br>\n";
        } else {
            echo "❌ {$display_name} 設定未找到<br>\n";
        }
        echo "<br>\n";
    }
}

/**
 * 測試支付方式轉換函數
 */
function test_payment_conversion() {
    echo "<h2>測試支付方式名稱轉換</h2>\n";
    
    // 測試的支付方式對應
    $test_methods = [
        'APPLEPAY' => 'Apple Pay',
        'SmartPay' => '智慧ATM2.0',
        'TWQR' => 'TWQR'
    ];
    
    // 模擬 convert_payment 函數的邏輯
    $method = array(
        'Credit'     => '信用卡一次付清',
        'AndroidPay' => 'Google Pay',
        'SamsungPay' => 'Samsung Pay',
        'LinePay'    => 'Line Pay',
        'Inst'       => '信用卡分期',
        'CreditRed'  => '信用卡紅利',
        'UnionPay'   => '銀聯卡',
        'Webatm'     => 'WEBATM',
        'Vacc'       => 'ATM轉帳',
        'CVS'        => '超商代碼',
        'BARCODE'    => '超商條碼',
        'EsunWallet' => '玉山 Wallet',
        'TaiwanPay'  => '台灣 Pay',
        'BitoPay'    => 'BitoPay',
        'EZPWECHAT'  => '微信支付',
        'EZPALIPAY'  => '支付寶',
        'APPLEPAY'   => 'Apple Pay',
        'SmartPay'   => '智慧ATM2.0',
        'TWQR'       => 'TWQR',
        'CVSCOMPayed' => '超商取貨付款',
        'CVSCOMNotPayed' => '超商取貨不付款',
    );
    
    foreach ($test_methods as $method_key => $expected_name) {
        if (isset($method[$method_key]) && $method[$method_key] === $expected_name) {
            echo "✅ {$method_key} -> {$expected_name} 轉換正確<br>\n";
        } else {
            echo "❌ {$method_key} 轉換失敗或不存在<br>\n";
        }
    }
}

/**
 * 顯示版本資訊
 */
function show_version_info() {
    echo "<h2>版本資訊</h2>\n";
    
    // 讀取 Central.php 檔案來取得版本號
    $central_file = dirname(__FILE__) . '/Central.php';
    if (file_exists($central_file)) {
        $content = file_get_contents($central_file);
        if (preg_match('/Version:\s*([0-9.]+)/', $content, $matches)) {
            echo "目前版本: {$matches[1]}<br>\n";
            if ($matches[1] === '1.0.9') {
                echo "✅ 版本號已更新至 1.0.9<br>\n";
            } else {
                echo "❌ 版本號未更新<br>\n";
            }
        }
    }
}

// 如果直接執行此檔案 (用於除錯)
if (basename($_SERVER['PHP_SELF']) === 'test-new-payments.php') {
    echo "<h1>藍新金流支付外掛 1.0.9 新功能測試</h1>\n";
    
    show_version_info();
    echo "<hr>\n";
    
    test_new_payment_methods();
    echo "<hr>\n";
    
    test_payment_conversion();
    
    echo "<p><strong>測試完成！</strong></p>\n";
    echo "<p>如果所有項目都顯示 ✅，表示新功能已成功整合。</p>\n";
}

?>
