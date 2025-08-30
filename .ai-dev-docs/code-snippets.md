# 程式碼片段與範例

## 1. 基本設定讀取

### 獲取插件設定
```php
$gateway_settings = get_option('woocommerce_newebpay_settings', '');

if (!empty($gateway_settings)) {
    $merchantID = $gateway_settings['MerchantID'];
    $hashKey    = $gateway_settings['HashKey'];
    $hashIV     = $gateway_settings['HashIV'];
    $testMode   = $gateway_settings['TestMode'];
}
```

### 判斷測試/正式環境
```php
if ($this->TestMode == 'yes') {
    $this->gateway = 'https://ccore.newebpay.com/MPG/mpg_gateway'; // 測試
} else {
    $this->gateway = 'https://core.newebpay.com/MPG/mpg_gateway'; // 正式
}
```

## 2. 日誌記錄

### 基本日誌記錄
```php
$logger = wc_get_logger();
$logger->info('付款處理開始', array('source' => 'newebpay'));
$logger->error('API 錯誤: ' . $error_message, array('source' => 'newebpay'));
```

### 詳細日誌記錄
```php
$context = array(
    'source' => 'newebpay',
    'order_id' => $order_id,
    'merchant_id' => $this->MerchantID
);

$logger->info('交易資料: ' . json_encode($transaction_data), $context);
```

## 3. 訂單處理

### 獲取訂單資訊
```php
$order = wc_get_order($order_id);
$order_total = $order->get_total();
$order_currency = $order->get_currency();
$billing_email = $order->get_billing_email();
```

### 更新訂單狀態
```php
$order->update_status('processing', '付款完成，等待處理');
$order->add_order_note('藍新金流付款成功，交易編號：' . $trade_no);
$order->payment_complete($trade_no);
```

## 4. 加密處理

### AES 加密範例
```php
// 假設 encProcess 類別的方法
$encrypt_data = array(
    'MerchantID' => $this->MerchantID,
    'OrderNo' => $order_id,
    'Amt' => $order_total
);

$encrypted_string = $this->encrypt($encrypt_data, $this->HashKey, $this->HashIV);
```

### 數據簽章驗證
```php
function verify_signature($data, $check_value, $hash_key, $hash_iv) {
    $generated_signature = $this->generate_signature($data, $hash_key, $hash_iv);
    return hash_equals($generated_signature, $check_value);
}
```

## 5. API 請求處理

### cURL 請求範例
```php
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($post_data),
    CURLOPT_HTTPHEADER => array(
        'Content-Type: application/x-www-form-urlencoded'
    ),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => true
));

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
```

### API 回應處理
```php
if ($http_code === 200) {
    $response_data = json_decode($response, true);
    
    if ($response_data['Status'] === 'SUCCESS') {
        // 處理成功回應
        $this->process_success_response($response_data);
    } else {
        // 處理錯誤回應
        $this->process_error_response($response_data);
    }
} else {
    // HTTP 錯誤處理
    $logger->error('API HTTP 錯誤: ' . $http_code);
}
```

## 6. 電子發票處理

### 開立發票範例
```php
$invoice_data = array(
    'MerchantID' => $this->InvMerchantID,
    'PostData' => array(
        'RespondType' => 'JSON',
        'Version' => '1.4',
        'TimeStamp' => time(),
        'TransNum' => $order_id,
        'MerchantOrderNo' => $order->get_order_number(),
        'BuyerName' => $order->get_billing_first_name() . $order->get_billing_last_name(),
        'BuyerEmail' => $order->get_billing_email(),
        'TotalAmt' => $order->get_total(),
        'TaxType' => $this->TaxType,
        'ItemName' => $this->get_order_items_name($order),
        'ItemCount' => $this->get_order_items_count($order),
        'ItemUnit' => '式',
        'ItemPrice' => $order->get_total(),
        'ItemAmt' => $order->get_total()
    )
);
```

## 7. 前端表單處理

### 付款表單產生
```php
function generate_payment_form($order_id) {
    $order = wc_get_order($order_id);
    
    $form_data = array(
        'MerchantID' => $this->MerchantID,
        'TradeInfo' => $this->get_encrypted_trade_info($order),
        'TradeSha' => $this->get_trade_sha($order),
        'Version' => '2.0'
    );
    
    $form_html = '<form id="newebpay_form" method="post" action="' . $this->gateway . '">';
    foreach ($form_data as $key => $value) {
        $form_html .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
    }
    $form_html .= '<input type="submit" value="前往付款">';
    $form_html .= '</form>';
    
    return $form_html;
}
```

## 8. 錯誤處理

### 標準錯誤處理
```php
try {
    $result = $this->process_payment($order_id);
    
    if ($result['result'] === 'success') {
        return $result;
    } else {
        throw new Exception($result['message']);
    }
} catch (Exception $e) {
    $logger->error('付款處理錯誤: ' . $e->getMessage(), array(
        'source' => 'newebpay',
        'order_id' => $order_id
    ));
    
    wc_add_notice('付款處理失敗，請稍後再試', 'error');
    return array(
        'result' => 'failure',
        'message' => $e->getMessage()
    );
}
```

## 9. Hook 和 Filter

### 自定義動作掛鉤
```php
// 付款完成後觸發
add_action('woocommerce_thankyou_newebpay', array($this, 'thankyou_page'));

// 管理員訂單頁面額外欄位
add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'admin_other_field'));

// 自定義付款完成處理
add_action('newebpay_payment_complete', array($this, 'handle_payment_complete'), 10, 2);
```

### 自定義過濾器
```php
// 修改付款閘道圖示
add_filter('woocommerce_newebpay_icon', function($icon_url) {
    return plugins_url('custom-icon.png', __FILE__);
});

// 自定義付款方式顯示名稱
add_filter('newebpay_payment_method_title', function($title, $payment_type) {
    if ($payment_type === 'CREDIT') {
        return '信用卡付款';
    }
    return $title;
}, 10, 2);
```

## 10. 測試用程式碼

### 模擬測試數據
```php
function get_test_order_data() {
    return array(
        'MerchantID' => 'MS350720220427184709',
        'OrderNo' => 'TEST_' . time(),
        'Amt' => 100,
        'ItemDesc' => '測試商品',
        'Email' => 'test@example.com',
        'LoginType' => 0,
        'CREDIT' => 1,
        'ANDROIDPAY' => 1,
        'SAMSUNGPAY' => 1,
        'LINEPAY' => 1,
        'ExpireDate' => date('Y-m-d', strtotime('+7 days'))
    );
}
```

### API 測試函數
```php
function test_api_connection() {
    $test_data = $this->get_test_order_data();
    $response = $this->send_api_request($this->gateway, $test_data);
    
    if ($response) {
        return true;
    } else {
        return false;
    }
}
```
