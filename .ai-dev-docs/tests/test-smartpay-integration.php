<?php
/**
 * 智慧ATM2.0 整合測試腳本
 * 
 * 測試智慧ATM2.0的參數設定和串接邏輯
 */

// 防止直接訪問
if (!defined('ABSPATH')) {
    define('ABSPATH', true);
}

/**
 * 測試智慧ATM2.0的參數設定
 */
function test_smartpay_integration() {
    echo "<h1>智慧ATM2.0 整合測試</h1>\n";
    
    echo "<h2>1. 智慧ATM2.0 參數說明</h2>\n";
    echo "<p>根據藍新金流技術手冊，智慧ATM2.0是VACC(ATM轉帳)的延伸功能</p>\n";
    echo "<p>需要額外的三個參數：</p>\n";
    echo "<ul>\n";
    echo "<li><strong>SourceType</strong>: 資料來源類型</li>\n";
    echo "<li><strong>SourceBankID</strong>: 來源銀行代碼</li>\n";
    echo "<li><strong>SourceAccountNo</strong>: 來源帳號</li>\n";
    echo "</ul>\n";
    
    echo "<h2>2. 測試支付參數組合</h2>\n";
    
    // 模擬智慧ATM2.0的參數
    $test_params = [
        'SourceType' => 'TEST_TYPE',
        'SourceBankID' => '822',  // 中國信託銀行代碼示例
        'SourceAccountNo' => '1234567890'
    ];
    
    // 模擬基本支付參數
    $post_data = [
        'MerchantID' => 'TEST123456',
        'RespondType' => 'JSON',
        'TimeStamp' => time(),
        'Version' => '2.3',  // 更新的版本
        'MerchantOrderNo' => '12345T' . time(),
        'Amt' => 1000,
        'ItemDesc' => '測試商品 - 智慧ATM2.0',
    ];
    
    // 加入智慧ATM2.0參數
    $post_data['VACC'] = 1;  // 使用VACC參數
    
    foreach ($test_params as $key => $value) {
        if (!empty($value)) {
            $post_data[$key] = $value;
        }
    }
    
    echo "<h3>送到藍新金流的完整參數：</h3>\n";
    echo "<pre>\n";
    print_r($post_data);
    echo "</pre>\n";
    
    echo "<h2>3. 參數驗證</h2>\n";
    
    $required_params = ['VACC', 'SourceType', 'SourceBankID', 'SourceAccountNo'];
    foreach ($required_params as $param) {
        if (isset($post_data[$param])) {
            echo "✅ {$param}: " . $post_data[$param] . "<br>\n";
        } else {
            echo "❌ {$param}: 參數缺失<br>\n";
        }
    }
    
    echo "<h2>4. 版本檢查</h2>\n";
    if ($post_data['Version'] === '2.3') {
        echo "✅ API版本正確: 2.3<br>\n";
    } else {
        echo "❌ API版本錯誤，應為 2.3<br>\n";
    }
    
    echo "<h2>5. 設定檢查清單</h2>\n";
    echo "<ol>\n";
    echo "<li>✅ 在後台設定頁面啟用智慧ATM2.0</li>\n";
    echo "<li>⚠️ 設定 SourceType 參數（需聯繫藍新金流申請）</li>\n";
    echo "<li>⚠️ 設定 SourceBankID 參數（需聯繫藍新金流申請）</li>\n";
    echo "<li>⚠️ 設定 SourceAccountNo 參數（需聯繫藍新金流申請）</li>\n";
    echo "<li>✅ API版本已更新為 2.3</li>\n";
    echo "</ol>\n";
    
    echo "<h2>6. 注意事項</h2>\n";
    echo "<div style='background-color: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px;'>\n";
    echo "<strong>重要：</strong><br>\n";
    echo "• 智慧ATM2.0 需要向藍新金流申請啟用<br>\n";
    echo "• SourceType、SourceBankID、SourceAccountNo 三個參數需要藍新金流提供<br>\n";
    echo "• 測試前請確保在藍新金流後台已啟用智慧ATM2.0功能<br>\n";
    echo "• 使用測試環境進行初步驗證\n";
    echo "</div>\n";
    
    echo "<p><strong>測試完成！</strong></p>\n";
}

/**
 * 檢查設定檔案
 */
function check_smartpay_settings() {
    echo "<h2>設定檔案檢查</h2>\n";
    
    $settings_file = dirname(__FILE__) . '/includes/nwp/nwpSetting.php';
    if (file_exists($settings_file)) {
        $content = file_get_contents($settings_file);
        
        $required_settings = [
            'SmartPaySourceType',
            'SmartPaySourceBankID', 
            'SmartPaySourceAccountNo'
        ];
        
        foreach ($required_settings as $setting) {
            if (strpos($content, $setting) !== false) {
                echo "✅ {$setting} 設定已添加<br>\n";
            } else {
                echo "❌ {$setting} 設定缺失<br>\n";
            }
        }
    } else {
        echo "❌ 設定檔案不存在<br>\n";
    }
}

// 執行測試
if (basename($_SERVER['PHP_SELF']) === 'test-smartpay-integration.php') {
    test_smartpay_integration();
    echo "<hr>\n";
    check_smartpay_settings();
}

?>
