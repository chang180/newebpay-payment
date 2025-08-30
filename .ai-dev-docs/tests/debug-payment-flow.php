<?php
/**
 * 除錯用腳本 - 驗證支付方式處理邏輯
 * 
 * 測試新的支付方式資料流程是否正確
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    // 模擬 WordPress 環境進行測試
    define('ABSPATH', true);
}

/**
 * 模擬測試支付方式資料處理
 */
function debug_payment_flow() {
    echo "<h1>支付方式資料流程除錯</h1>\n";
    
    echo "<h2>1. 測試支付方式設定讀取</h2>\n";
    
    // 模擬從設定中讀取啟用的支付方式
    $enabled_payments = [
        'NwpPaymentMethodAPPLEPAY' => 'yes',
        'NwpPaymentMethodSmartPay' => 'yes', 
        'NwpPaymentMethodTWQR' => 'yes'
    ];
    
    $selected_payments = [];
    foreach ($enabled_payments as $key => $value) {
        if ($value === 'yes') {
            $payment_type = str_replace('NwpPaymentMethod', '', $key);
            $selected_payments[$payment_type] = 1;
        }
    }
    
    echo "啟用的支付方式：<br>\n";
    foreach ($selected_payments as $payment => $status) {
        echo "- {$payment}: {$status}<br>\n";
    }
    
    echo "<h2>2. 測試用戶選擇的支付方式</h2>\n";
    
    // 模擬用戶選擇的支付方式
    $user_selected = 'APPLEPAY'; // 模擬用戶選擇 Apple Pay
    
    echo "用戶選擇：{$user_selected}<br>\n";
    
    echo "<h2>3. 測試送到藍新金流的參數</h2>\n";
    
    // 模擬組建支付參數
    $post_data = [
        'MerchantID' => 'TEST123456',
        'RespondType' => 'JSON',
        'TimeStamp' => time(),
        'Version' => '2.0',
        'MerchantOrderNo' => '12345T' . time(),
        'Amt' => 1000,
        'ItemDesc' => '測試商品',
    ];
    
    // 加入選擇的支付方式
    if (!empty($user_selected)) {
        $post_data[strtoupper($user_selected)] = 1;
    }
    
    echo "送到藍新金流的參數：<br>\n";
    echo "<pre>\n";
    print_r($post_data);
    echo "</pre>\n";
    
    echo "<h2>4. 驗證結果</h2>\n";
    
    $expected_params = ['APPLEPAY', 'SMARTPAY', 'TWQR'];
    
    foreach ($expected_params as $param) {
        if (isset($post_data[$param])) {
            echo "✅ {$param} 參數正確設定<br>\n";
        } else {
            echo "❌ {$param} 參數缺失<br>\n";
        }
    }
    
    echo "<h2>5. 支付方式名稱轉換測試</h2>\n";
    
    $conversion_map = [
        'APPLEPAY' => 'Apple Pay',
        'SmartPay' => '智慧ATM2.0',
        'TWQR' => 'TWQR'
    ];
    
    foreach ($conversion_map as $key => $name) {
        echo "- {$key} → {$name}<br>\n";
    }
    
    echo "<p><strong>除錯完成！</strong></p>\n";
}

/**
 * 檢查檔案修改狀況
 */
function check_file_modifications() {
    echo "<h2>檔案修改檢查</h2>\n";
    
    $files_to_check = [
        'Central.php' => '版本號',
        'includes/nwp/nwpSetting.php' => '支付設定',
        'includes/nwp/nwpMPG.php' => '支付邏輯',
        'readme.txt' => '說明文件'
    ];
    
    foreach ($files_to_check as $file => $description) {
        $full_path = dirname(__FILE__) . '/' . $file;
        if (file_exists($full_path)) {
            $mod_time = filemtime($full_path);
            $mod_date = date('Y-m-d H:i:s', $mod_time);
            echo "✅ {$description} ({$file}) - 最後修改: {$mod_date}<br>\n";
        } else {
            echo "❌ {$description} ({$file}) - 檔案不存在<br>\n";
        }
    }
}

// 執行除錯
if (basename($_SERVER['PHP_SELF']) === 'debug-payment-flow.php') {
    debug_payment_flow();
    echo "<hr>\n";
    check_file_modifications();
}

?>
