# Newebpay Logger 使用指南

## 📝 概述

Newebpay Payment 插件包含一個統一的日誌記錄系統，用於記錄支付處理、API 調用、錯誤和除錯資訊。

## 🏗️ 架構設計

### Logger 類別特性
- **單例模式**: 全域統一的日誌管理
- **自動輪替**: 檔案超過 5MB 自動備份
- **安全保護**: `.htaccess` 防止外部存取
- **條件啟用**: 僅在 `WP_DEBUG` 模式下啟用
- **分類記錄**: 支援不同類型的日誌檔案

### 目錄結構
```
logs/
├── .htaccess          # 存取保護
├── index.php          # 防止目錄列表
├── newebpay.log       # 一般日誌
├── payment.log        # 支付相關日誌
├── api.log           # API 相關日誌
└── *.bak             # 備份檔案
```

## 🔧 基本使用

### 輔助函數（推薦）
```php
// 一般訊息
newebpay_log('info', '支付處理開始', array('order_id' => 12345));

// 錯誤訊息
newebpay_log('error', '支付失敗', array(
    'error_code' => 'E001', 
    'message' => 'Invalid parameters'
));

// 支付相關
newebpay_log('payment', '支付成功', array(
    'order_id' => 12345, 
    'amount' => 1000
));

// API 相關
newebpay_log('api', 'API 請求送出', array(
    'endpoint' => 'https://core.newebpay.com/MPG/mpg_gateway'
));
```

### 直接使用 Logger 類別
```php
$logger = Newebpay_Logger::get_instance();

$logger->info('一般訊息');
$logger->error('錯誤訊息');
$logger->warning('警告訊息');
$logger->debug('除錯訊息');
$logger->payment('支付相關訊息');
$logger->api('API 相關訊息');
```

## 📋 實際應用範例

### 1. 支付處理日誌
```php
public function process_payment($order_id) {
    newebpay_log('payment', 'Processing payment for order', array(
        'order_id' => $order_id,
        'payment_method' => $this->id
    ));
    
    try {
        $order = wc_get_order($order_id);
        
        // 支付處理邏輯...
        
        newebpay_log('payment', 'Payment processing completed', array(
            'order_id' => $order_id,
            'status' => 'success'
        ));
        
        return array('result' => 'success', 'redirect' => $redirect_url);
        
    } catch (Exception $e) {
        newebpay_log('error', 'Payment processing failed', array(
            'order_id' => $order_id,
            'error' => $e->getMessage()
        ));
        
        return array('result' => 'failure');
    }
}
```

### 2. API 調用日誌
```php
// API 請求前
newebpay_log('api', 'Sending API request', array(
    'endpoint' => $this->gateway,
    'merchant_order_no' => $merchant_order_no
));

// API 回應處理
if ($response_success) {
    newebpay_log('api', 'API response success', array(
        'trade_no' => $response_data['TradeNo']
    ));
} else {
    newebpay_log('api', 'API response failed', array(
        'error_code' => $response_data['Status'],
        'error_message' => $response_data['Message']
    ));
}
```

### 3. 錯誤處理日誌
```php
// 驗證失敗
if (!$this->chkShaIsVaildByReturnData($_REQUEST)) {
    newebpay_log('error', 'SHA validation failed', array(
        'expected_sha' => $expected_sha,
        'received_sha' => $_REQUEST['TradeSha']
    ));
    
    echo 'SHA validation fail';
    exit;
}
```

### 4. 除錯資訊日誌
```php
// 詳細除錯資訊
if (defined('WP_DEBUG') && WP_DEBUG) {
    newebpay_log('debug', 'Payment arguments prepared', array(
        'post_data' => $post_data,
        'encrypted_data' => $aes_encrypted,
        'hash' => $sha256_hash
    ));
}
```

## 🛠️ 管理功能

### 檢視日誌檔案
```php
$logger = Newebpay_Logger::get_instance();

// 獲取所有日誌檔案列表
$log_files = $logger->get_log_files();

// 讀取最近 50 行日誌
$recent_logs = $logger->read_log('newebpay.log', 50);

// 讀取支付相關日誌
$payment_logs = $logger->read_log('payment.log', 100);
```

### 清理日誌
```php
$logger = Newebpay_Logger::get_instance();

// 清理所有日誌檔案
$logger->clear_logs();
```

## 📊 日誌格式

### 標準格式
```
[2025-08-30 09:45:23] [INFO] 支付處理開始 {"order_id":12345,"payment_method":"newebpay"}
[2025-08-30 09:45:24] [ERROR] 支付失敗 {"error_code":"E001","message":"Invalid parameters"}
```

### 日誌等級
- **INFO**: 一般資訊訊息
- **ERROR**: 錯誤訊息
- **WARNING**: 警告訊息
- **DEBUG**: 除錯訊息
- **PAYMENT**: 支付相關訊息
- **API**: API 相關訊息

## ⚠️ 注意事項

1. **僅在除錯模式啟用**: 日誌功能僅在 `WP_DEBUG` 為 `true` 時啟用
2. **自動檔案輪替**: 檔案超過 5MB 會自動建立備份並開始新檔案
3. **安全保護**: logs 目錄受 `.htaccess` 保護，無法從外部存取
4. **效能考量**: 日誌記錄會略微影響效能，正式環境建議關閉除錯模式
5. **磁碟空間**: 定期清理舊的日誌檔案以節省磁碟空間

## 🔐 安全性

- logs 目錄包含 `.htaccess` 檔案防止 HTTP 存取
- 包含 `index.php` 檔案防止目錄瀏覽
- 敏感資訊應避免記錄到日誌中
- 定期清理包含敏感資訊的日誌檔案

---

> 💡 **最佳實務**: 在開發和測試階段啟用詳細日誌，在正式環境中關閉或僅記錄關鍵錯誤。
