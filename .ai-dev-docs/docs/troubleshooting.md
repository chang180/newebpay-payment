# 問題排除指南

## 常見問題與解決方案

### 1. 付款相關問題

#### 問題：付款後訂單狀態未更新
**可能原因：**
- 回調 URL 無法正常存取
- 簽章驗證失敗
- 網路連線問題

**排除步驟：**
1. 檢查回調 URL 是否可從外部存取：`{site_url}/?wc-api=WC_newebpay&callback=return`
2. 確認 HashKey 和 HashIV 設定正確
3. 查看錯誤日誌：WooCommerce > 狀態 > 日誌
4. 測試網站 SSL 憑證是否有效

```php
// 測試回調 URL
function test_callback_url() {
    $callback_url = add_query_arg('wc-api', 'WC_newebpay', home_url('/')) . '&callback=return';
    echo "回調 URL: " . $callback_url . "\n";
    
    $response = wp_remote_get($callback_url);
    if (is_wp_error($response)) {
        echo "錯誤: " . $response->get_error_message();
    } else {
        echo "HTTP 狀態: " . wp_remote_retrieve_response_code($response);
    }
}
```

#### 問題：付款頁面無法載入
**可能原因：**
- API 金鑰錯誤
- 加密數據格式錯誤
- 網路防火牆阻擋

**排除步驟：**
1. 確認 MerchantID、HashKey、HashIV 正確
2. 檢查測試/正式環境設定
3. 驗證加密數據格式
4. 檢查伺服器防火牆設定

### 2. 電子發票問題

#### 問題：電子發票開立失敗
**可能原因：**
- 發票 API 金鑰錯誤
- 商店未開通發票功能
- 發票格式驗證失敗

**排除步驟：**
1. 確認電子發票設定正確
2. 檢查商店是否已開通發票功能
3. 驗證發票開立資料格式
4. 查看發票 API 回應錯誤訊息

```php
// 測試發票設定
function test_invoice_settings() {
    $settings = get_option('woocommerce_newebpay_settings');
    
    if (empty($settings['InvMerchantID'])) {
        echo "錯誤: 未設定發票商店代號\n";
    }
    
    if (empty($settings['InvHashKey']) || empty($settings['InvHashIV'])) {
        echo "錯誤: 未設定發票加密金鑰\n";
    }
    
    if ($settings['eiChk'] !== 'yes') {
        echo "提醒: 電子發票功能未啟用\n";
    }
}
```

### 3. 設定相關問題

#### 問題：後台設定頁面空白或錯誤
**可能原因：**
- PHP 錯誤
- 記憶體不足
- 外掛衝突

**排除步驟：**
1. 檢查 PHP 錯誤日誌
2. 增加 PHP 記憶體限制
3. 停用其他外掛測試
4. 檢查 WordPress 和 WooCommerce 版本相容性

#### 問題：設定保存後遺失
**可能原因：**
- 資料庫權限問題
- WordPress nonce 驗證失敗
- 伺服器 session 問題

**排除步驟：**
1. 檢查資料庫寫入權限
2. 清除瀏覽器快取和 cookies
3. 檢查伺服器 session 設定
4. 驗證 WordPress 安全 nonce

### 4. API 連線問題

#### 問題：API 請求逾時
**可能原因：**
- 網路連線不穩定
- 伺服器防火牆阻擋
- API 服務異常

**排除步驟：**
1. 測試網路連線狀況
2. 檢查防火牆設定
3. 增加 cURL 逾時設定
4. 聯繫藍新金流技術支援

```php
// 測試 API 連線
function test_api_connectivity() {
    $test_urls = array(
        'test' => 'https://ccore.newebpay.com/MPG/mpg_gateway',
        'prod' => 'https://core.newebpay.com/MPG/mpg_gateway'
    );
    
    foreach ($test_urls as $env => $url) {
        $start_time = microtime(true);
        $response = wp_remote_get($url, array('timeout' => 10));
        $end_time = microtime(true);
        
        echo "環境: {$env}\n";
        echo "URL: {$url}\n";
        echo "回應時間: " . round(($end_time - $start_time) * 1000, 2) . " ms\n";
        
        if (is_wp_error($response)) {
            echo "錯誤: " . $response->get_error_message() . "\n";
        } else {
            echo "HTTP 狀態: " . wp_remote_retrieve_response_code($response) . "\n";
        }
        echo "---\n";
    }
}
```

### 5. 加密解密問題

#### 問題：數據加密失敗
**可能原因：**
- HashKey 或 HashIV 錯誤
- 加密演算法不支援
- 字元編碼問題

**排除步驟：**
1. 驗證 HashKey 和 HashIV 格式
2. 檢查伺服器 OpenSSL 支援
3. 確認字元編碼為 UTF-8
4. 測試加密解密功能

```php
// 測試加密功能
function test_encryption() {
    $test_data = "測試數據123";
    $hash_key = "your_hash_key";
    $hash_iv = "your_hash_iv";
    
    // 假設有加密方法
    $encrypted = $this->encrypt_data($test_data, $hash_key, $hash_iv);
    $decrypted = $this->decrypt_data($encrypted, $hash_key, $hash_iv);
    
    echo "原始數據: {$test_data}\n";
    echo "加密後: {$encrypted}\n";
    echo "解密後: {$decrypted}\n";
    echo "加密測試: " . ($test_data === $decrypted ? "成功" : "失敗") . "\n";
}
```

### 6. 除錯工具與技巧

#### 啟用除錯模式
在 `wp-config.php` 中加入：
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### 記錄 API 請求回應
```php
function log_api_request($url, $data, $response) {
    $log_data = array(
        'time' => current_time('mysql'),
        'url' => $url,
        'request' => $data,
        'response' => $response
    );
    
    error_log('Newebpay API: ' . json_encode($log_data, JSON_UNESCAPED_UNICODE));
}
```

#### 檢查系統需求
```php
function check_system_requirements() {
    $requirements = array(
        'PHP版本' => array(
            'required' => '8.0',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '8.0', '>=')
        ),
        'cURL支援' => array(
            'required' => true,
            'current' => function_exists('curl_init'),
            'status' => function_exists('curl_init')
        ),
        'OpenSSL支援' => array(
            'required' => true,
            'current' => extension_loaded('openssl'),
            'status' => extension_loaded('openssl')
        )
    );
    
    foreach ($requirements as $name => $req) {
        $status = $req['status'] ? '✅' : '❌';
        echo "{$status} {$name}: {$req['current']}\n";
    }
}
```

### 7. 聯繫技術支援

#### 準備資訊
聯繫技術支援前，請準備以下資訊：
- WordPress 版本
- WooCommerce 版本
- PHP 版本
- 插件版本
- 錯誤訊息和日誌
- 重現步驟
- 商店代號（去除敏感資訊）

#### 聯繫方式
- 藍新金流客服: cs@newebpay.com
- 技術文檔: https://www.newebpay.com/website/Page/content/download_api#2

### 8. 效能優化

#### 快取設定
```php
// 避免快取付款頁面
function exclude_payment_pages_from_cache($page_ids) {
    $checkout_page_id = wc_get_page_id('checkout');
    $page_ids[] = $checkout_page_id;
    return $page_ids;
}
add_filter('cache_exclude_pages', 'exclude_payment_pages_from_cache');
```

#### 資料庫優化
```php
// 定期清理過期的交易記錄
function cleanup_expired_transactions() {
    global $wpdb;
    
    $expired_date = date('Y-m-d H:i:s', strtotime('-30 days'));
    
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->prefix}newebpay_transactions 
        WHERE created_at < %s AND status = 'expired'
    ", $expired_date));
}
```
