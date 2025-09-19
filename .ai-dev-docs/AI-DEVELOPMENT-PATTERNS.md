# AI 開發模式與慣例

## 🎨 程式碼架構模式

### WordPress 外掛標準模式
```php
// 主要外掛檔案結構
<?php
/**
 * Plugin Name: NewebPay Payment Gateway
 * Description: 藍新金流付款閘道
 * Version: 1.0.0
 * Author: Your Name
 */

// 安全性檢查
if (!defined('ABSPATH')) {
    exit;
}

// 常數定義
define('NEWEBPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NEWEBPAY_PLUGIN_PATH', plugin_dir_path(__FILE__));

// 自動載入
require_once NEWEBPAY_PLUGIN_PATH . 'includes/class-newebpay-payment.php';

// 外掛啟動
new NewebPayPayment();
```

### 類別定義模式
```php
// 標準類別結構
class NewebPayGateway extends WC_Payment_Gateway {
    
    // 屬性定義
    private $api_key;
    private $hash_key;
    private $hash_iv;
    
    // 建構函數
    public function __construct() {
        $this->id = 'newebpay';
        $this->method_title = '藍新金流';
        $this->method_description = '使用藍新金流進行付款';
        
        $this->init_form_fields();
        $this->init_settings();
        
        // Hook 註冊
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }
    
    // 公開方法
    public function process_payment($order_id) {
        // 實作邏輯
    }
    
    // 私有方法
    private function encrypt_data($data) {
        // 實作邏輯
    }
}
```

## 🔐 安全性開發模式

### 資料驗證模式
```php
// 輸入驗證標準
class NewebPayValidator {
    
    public static function validate_order_data($data) {
        // 必要欄位檢查
        $required_fields = ['MerchantOrderNo', 'Amt', 'ItemDesc'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("必要欄位 {$field} 不能為空");
            }
        }
        
        // 資料類型驗證
        if (!is_numeric($data['Amt']) || $data['Amt'] <= 0) {
            throw new Exception('金額必須為正數');
        }
        
        // 資料清理
        $data['ItemDesc'] = sanitize_text_field($data['ItemDesc']);
        
        return $data;
    }
}
```

### 加密處理模式
```php
// 加密解密標準
class NewebPaySecurity {
    
    private $hash_key;
    private $hash_iv;
    
    public function encrypt($data) {
        // 資料序列化
        $query_string = http_build_query($data);
        
        // AES 加密
        $encrypted = openssl_encrypt(
            $query_string,
            'aes-256-cbc',
            $this->hash_key,
            0,
            $this->hash_iv
        );
        
        return $encrypted;
    }
    
    public function create_check_value($data) {
        // 建立檢查碼
        $check_string = "HashKey={$this->hash_key}&" . http_build_query($data) . "&HashIV={$this->hash_iv}";
        return strtoupper(hash('sha256', $check_string));
    }
}
```

## 🔄 API 通訊模式

### HTTP 請求標準
```php
class NewebPayAPI {
    
    private $api_url;
    private $timeout = 30;
    
    public function send_request($endpoint, $data, $method = 'POST') {
        $url = $this->api_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($data),
            'sslverify' => true
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            throw new Exception('API 請求失敗: ' . $response->get_error_message());
        }
        
        return $this->parse_response($response);
    }
    
    private function parse_response($response) {
        $body = wp_remote_retrieve_body($response);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            throw new Exception("API 回應錯誤: HTTP {$status_code}");
        }
        
        return json_decode($body, true);
    }
}
```

## 📝 日誌記錄模式

### 日誌標準
```php
class NewebPayLogger {
    
    private $log_file;
    private $log_level;
    
    public function info($message, $context = array()) {
        $this->log('INFO', $message, $context);
    }
    
    public function error($message, $context = array()) {
        $this->log('ERROR', $message, $context);
    }
    
    private function log($level, $message, $context) {
        if (!$this->should_log($level)) {
            return;
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $context_string = !empty($context) ? json_encode($context) : '';
        
        $log_entry = "[{$timestamp}] {$level}: {$message} {$context_string}" . PHP_EOL;
        
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
```

## 🎯 Hook 使用模式

### Action Hook 模式
```php
// 外掛啟動時
add_action('plugins_loaded', array($this, 'init'));

// WooCommerce 整合
add_action('woocommerce_payment_gateways', array($this, 'add_gateway'));

// API 回調處理
add_action('woocommerce_api_newebpay', array($this, 'handle_callback'));

// 管理介面
add_action('admin_menu', array($this, 'add_admin_menu'));
```

### Filter Hook 模式
```php
// 閘道設定過濾
add_filter('woocommerce_payment_gateways', array($this, 'add_newebpay_gateway'));

// 訂單資料過濾
add_filter('woocommerce_new_order_data', array($this, 'modify_order_data'));

// 樣式載入過濾
add_filter('woocommerce_enqueue_styles', array($this, 'add_custom_styles'));
```

## 🚨 錯誤處理模式

### 例外處理標準
```php
public function process_payment($order_id) {
    try {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            throw new Exception('訂單不存在');
        }
        
        $payment_data = $this->prepare_payment_data($order);
        $response = $this->api->send_payment_request($payment_data);
        
        return array(
            'result' => 'success',
            'redirect' => $response['payment_url']
        );
        
    } catch (Exception $e) {
        $this->logger->error('付款處理失敗', array(
            'order_id' => $order_id,
            'error' => $e->getMessage()
        ));
        
        wc_add_notice('付款處理失敗: ' . $e->getMessage(), 'error');
        
        return array(
            'result' => 'failure'
        );
    }
}
```

## 🎨 前端開發模式

### JavaScript 標準
```javascript
// jQuery 包裝器
(function($) {
    'use strict';
    
    // 付款表單處理
    var NewebPayForm = {
        
        init: function() {
            this.bindEvents();
            this.validateForm();
        },
        
        bindEvents: function() {
            $('#newebpay-payment-form').on('submit', this.handleSubmit);
        },
        
        handleSubmit: function(e) {
            e.preventDefault();
            
            // 表單驗證
            if (!NewebPayForm.validateForm()) {
                return false;
            }
            
            // 提交表單
            this.submit();
        },
        
        validateForm: function() {
            // 驗證邏輯
            return true;
        }
    };
    
    // DOM 載入完成後初始化
    $(document).ready(function() {
        NewebPayForm.init();
    });
    
})(jQuery);
```

### CSS 命名慣例
```css
/* BEM 命名法 */
.newebpay-payment {}
.newebpay-payment__form {}
.newebpay-payment__field {}
.newebpay-payment__button {}
.newebpay-payment__button--primary {}
.newebpay-payment__message {}
.newebpay-payment__message--error {}
.newebpay-payment__message--success {}
```

## 📋 資料庫操作模式

### 自訂表格
```php
// 建立自訂表格
public function create_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'newebpay_transactions';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id int(11) NOT NULL AUTO_INCREMENT,
        order_id int(11) NOT NULL,
        transaction_id varchar(255) NOT NULL,
        status varchar(50) NOT NULL,
        amount decimal(10,2) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY order_id (order_id),
        KEY transaction_id (transaction_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

這些模式為 AI 開發提供了明確的實作規範，確保程式碼品質和一致性。
