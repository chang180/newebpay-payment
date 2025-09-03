# AI 常見開發任務範例

## 🎯 任務類型索引

### 🔧 基礎功能任務
- [新增付款方式](#新增付款方式)
- [修改付款流程](#修改付款流程)
- [更新 API 介面](#更新-api-介面)
- [調整安全設定](#調整安全設定)

### 🎨 前端任務
- [客製化付款表單](#客製化付款表單)
- [調整結帳流程](#調整結帳流程)
- [修改樣式設計](#修改樣式設計)

### ⚙️ 後台任務
- [新增設定選項](#新增設定選項)
- [建立報表功能](#建立報表功能)
- [管理交易記錄](#管理交易記錄)

### 🐛 問題修復任務
- [修復支付錯誤](#修復支付錯誤)
- [解決回調問題](#解決回調問題)
- [優化效能問題](#優化效能問題)

### 🌐 現代化任務 (v1.0.10+)
- [WooCommerce Blocks 整合](#woocommerce-blocks-整合)
- [多語言本地化](#多語言本地化)
- [相容性升級](#相容性升級)

---

## 🔧 基礎功能任務

### 新增付款方式

**任務描述**: 在現有的藍新金流閘道中新增新的付款方式

**涉及檔案**:
- `includes/class-newebpay-gateway.php`
- `templates/payment-form.php`
- `assets/js/frontend.js`

**實作步驟**:

1. **修改閘道類別**:
```php
// 在 class-newebpay-gateway.php 中
public function init_form_fields() {
    $this->form_fields = array(
        // 現有欄位...
        'enable_credit_card' => array(
            'title' => '啟用信用卡',
            'type' => 'checkbox',
            'label' => '啟用信用卡付款',
            'default' => 'yes'
        ),
        'enable_webatm' => array(
            'title' => '啟用 WebATM',
            'type' => 'checkbox',
            'label' => '啟用 WebATM 付款',
            'default' => 'no'
        ),
        'enable_vacc' => array(
            'title' => '啟用虛擬帳號',
            'type' => 'checkbox',
            'label' => '啟用虛擬帳號付款',
            'default' => 'no'
        )
    );
}

private function get_enabled_payment_methods() {
    $methods = array();
    
    if ($this->get_option('enable_credit_card') === 'yes') {
        $methods[] = 'CREDIT';
    }
    if ($this->get_option('enable_webatm') === 'yes') {
        $methods[] = 'WEBATM';
    }
    if ($this->get_option('enable_vacc') === 'yes') {
        $methods[] = 'VACC';
    }
    
    return implode(',', $methods);
}
```

2. **更新支付資料準備**:
```php
private function prepare_payment_data($order) {
    $data = array(
        'MerchantID' => $this->merchant_id,
        'MerchantOrderNo' => $order->get_order_number(),
        'Amt' => $order->get_total(),
        'ItemDesc' => $this->get_order_description($order),
        'PaymentType' => $this->get_enabled_payment_methods(),
        // 其他必要欄位...
    );
    
    return $data;
}
```

### 修改付款流程

**任務描述**: 調整付款處理流程，加入額外的驗證或處理步驟

**涉及檔案**:
- `includes/class-newebpay-gateway.php`
- `includes/class-newebpay-validator.php`

**實作範例**:

```php
// 在 process_payment 方法中加入額外驗證
public function process_payment($order_id) {
    try {
        $order = wc_get_order($order_id);
        
        // 額外驗證：檢查庫存
        if (!$this->validate_stock($order)) {
            throw new Exception('商品庫存不足');
        }
        
        // 額外驗證：檢查用戶權限
        if (!$this->validate_user_permission($order)) {
            throw new Exception('用戶權限不足');
        }
        
        // 準備付款資料
        $payment_data = $this->prepare_payment_data($order);
        
        // 送出付款請求
        $response = $this->api->send_payment_request($payment_data);
        
        // 記錄交易
        $this->logger->info('付款請求已送出', array(
            'order_id' => $order_id,
            'payment_data' => $payment_data
        ));
        
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
        return array('result' => 'failure');
    }
}

private function validate_stock($order) {
    foreach ($order->get_items() as $item) {
        $product = $item->get_product();
        if ($product && !$product->has_enough_stock($item->get_quantity())) {
            return false;
        }
    }
    return true;
}
```

### 更新 API 介面

**任務描述**: 更新與藍新金流的 API 通訊介面

**涉及檔案**:
- `includes/class-newebpay-api.php`
- `includes/helpers/constants.php`

**實作範例**:

```php
// 新增 API 端點
class NewebPayAPI {
    
    private $endpoints = array(
        'payment' => '/MPG/mpg_gateway',
        'query' => '/API/QueryTradeInfo',
        'refund' => '/API/CreditCard/Close',  // 新增退款端點
    );
    
    // 新增退款方法
    public function process_refund($transaction_id, $amount, $reason = '') {
        $data = array(
            'MerchantID' => $this->merchant_id,
            'Version' => '1.0',
            'RespondType' => 'JSON',
            'Amt' => $amount,
            'MerchantOrderNo' => $transaction_id,
            'CloseType' => '2', // 退款
            'Cancel' => '0'
        );
        
        if (!empty($reason)) {
            $data['Reason'] = $reason;
        }
        
        // 加密資料
        $encrypted_data = $this->security->encrypt($data);
        
        // 送出請求
        return $this->send_request('refund', array(
            'MerchantID' => $this->merchant_id,
            'PostData_' => $encrypted_data
        ));
    }
    
    // 新增交易查詢方法
    public function query_transaction($order_number) {
        $data = array(
            'MerchantID' => $this->merchant_id,
            'Version' => '1.1',
            'RespondType' => 'JSON',
            'MerchantOrderNo' => $order_number,
            'Amt' => $this->get_order_amount($order_number)
        );
        
        $encrypted_data = $this->security->encrypt($data);
        
        return $this->send_request('query', array(
            'MerchantID' => $this->merchant_id,
            'PostData_' => $encrypted_data
        ));
    }
}
```

---

## 🎨 前端任務

### 客製化付款表單

**任務描述**: 修改付款表單的外觀和行為

**涉及檔案**:
- `templates/payment-form.php`
- `assets/css/frontend.css`
- `assets/js/frontend.js`

**實作範例**:

```php
<!-- templates/payment-form.php -->
<div class="newebpay-payment-form">
    <div class="payment-methods">
        <?php if ($this->get_option('enable_credit_card') === 'yes'): ?>
        <label class="payment-method">
            <input type="radio" name="payment_method" value="credit_card" checked>
            <span class="payment-method__label">信用卡</span>
            <img src="<?php echo NEWEBPAY_PLUGIN_URL; ?>assets/images/credit-card.png" alt="信用卡">
        </label>
        <?php endif; ?>
        
        <?php if ($this->get_option('enable_webatm') === 'yes'): ?>
        <label class="payment-method">
            <input type="radio" name="payment_method" value="webatm">
            <span class="payment-method__label">WebATM</span>
            <img src="<?php echo NEWEBPAY_PLUGIN_URL; ?>assets/images/webatm.png" alt="WebATM">
        </label>
        <?php endif; ?>
    </div>
    
    <div class="payment-notice">
        <p>請選擇付款方式後，點擊「確認付款」按鈕</p>
    </div>
</div>
```

```css
/* assets/css/frontend.css */
.newebpay-payment-form {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
}

.payment-methods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.payment-method {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-method:hover {
    border-color: #0073aa;
    background-color: #f0f8ff;
}

.payment-method input[type="radio"]:checked + .payment-method__label {
    color: #0073aa;
    font-weight: bold;
}
```

### 調整結帳流程

**任務描述**: 在結帳過程中加入額外的步驟或驗證

**涉及檔案**:
- `includes/class-newebpay-gateway.php`
- `assets/js/frontend.js`

**實作範例**:

```javascript
// assets/js/frontend.js
(function($) {
    'use strict';
    
    var NewebPayCheckout = {
        
        init: function() {
            this.bindEvents();
            this.setupValidation();
        },
        
        bindEvents: function() {
            $('body').on('checkout_place_order_newebpay', this.handlePlaceOrder);
            $(document).on('change', 'input[name="payment_method"]', this.handlePaymentMethodChange);
        },
        
        handlePlaceOrder: function() {
            // 額外的前端驗證
            if (!NewebPayCheckout.validatePaymentData()) {
                alert('請確認付款資訊正確');
                return false;
            }
            
            // 顯示載入狀態
            $('.payment-box').append('<div class="loading">處理中...</div>');
            
            return true;
        },
        
        validatePaymentData: function() {
            var selectedMethod = $('input[name="payment_method"]:checked').val();
            
            if (selectedMethod === 'newebpay') {
                var paymentType = $('input[name="newebpay_payment_type"]:checked').val();
                if (!paymentType) {
                    return false;
                }
            }
            
            return true;
        },
        
        handlePaymentMethodChange: function() {
            if ($(this).val() === 'newebpay') {
                $('.newebpay-payment-options').slideDown();
            } else {
                $('.newebpay-payment-options').slideUp();
            }
        }
    };
    
    $(document).ready(function() {
        NewebPayCheckout.init();
    });
    
})(jQuery);
```

---

## ⚙️ 後台任務

### 新增設定選項

**任務描述**: 在後台新增新的設定選項

**涉及檔案**:
- `includes/class-newebpay-admin.php`
- `templates/admin/settings-page.php`

**實作範例**:

```php
// 在 class-newebpay-admin.php 中
public function init_form_fields() {
    $this->form_fields = array_merge($this->form_fields, array(
        'debug_mode' => array(
            'title' => '除錯模式',
            'type' => 'checkbox',
            'label' => '啟用除錯模式（記錄詳細日誌）',
            'default' => 'no',
            'description' => '啟用後會記錄更詳細的交易日誌'
        ),
        'auto_complete' => array(
            'title' => '自動完成訂單',
            'type' => 'checkbox',
            'label' => '付款成功後自動完成訂單',
            'default' => 'no'
        ),
        'payment_timeout' => array(
            'title' => '付款超時時間',
            'type' => 'number',
            'default' => '30',
            'description' => '分鐘',
            'custom_attributes' => array(
                'min' => '5',
                'max' => '120'
            )
        )
    ));
}
```

### 建立報表功能

**任務描述**: 新增交易報表和統計功能

**涉及檔案**:
- `includes/class-newebpay-admin.php`
- `templates/admin/reports.php`

**實作範例**:

```php
// 新增報表頁面
public function add_admin_pages() {
    add_submenu_page(
        'woocommerce',
        '藍新金流報表',
        '藍新金流報表',
        'manage_woocommerce',
        'newebpay-reports',
        array($this, 'render_reports_page')
    );
}

public function render_reports_page() {
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-01');
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');
    
    $report_data = $this->get_transaction_report($start_date, $end_date);
    
    include NEWEBPAY_PLUGIN_PATH . 'templates/admin/reports.php';
}

private function get_transaction_report($start_date, $end_date) {
    global $wpdb;
    
    $query = $wpdb->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as transaction_count,
            SUM(amount) as total_amount,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount
        FROM {$wpdb->prefix}newebpay_transactions 
        WHERE created_at BETWEEN %s AND %s
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ", $start_date, $end_date);
    
    return $wpdb->get_results($query);
}
```

---

## 🐛 問題修復任務

### 修復支付錯誤

**任務描述**: 解決常見的支付處理錯誤

**常見錯誤類型**:
1. 加密資料格式錯誤
2. API 回應解析失敗
3. 訂單狀態更新失敗

**修復範例**:

```php
// 修復加密資料問題
private function encrypt_data($data) {
    try {
        // 確保資料格式正確
        $data = $this->sanitize_payment_data($data);
        
        // 建立查詢字串
        $query_string = http_build_query($data);
        
        // 進行加密
        $encrypted = openssl_encrypt(
            $query_string,
            'aes-256-cbc',
            $this->hash_key,
            0,
            $this->hash_iv
        );
        
        if ($encrypted === false) {
            throw new Exception('資料加密失敗');
        }
        
        return $encrypted;
        
    } catch (Exception $e) {
        $this->logger->error('加密失敗', array(
            'data' => $data,
            'error' => $e->getMessage()
        ));
        throw $e;
    }
}

private function sanitize_payment_data($data) {
    // 移除空值
    $data = array_filter($data, function($value) {
        return $value !== null && $value !== '';
    });
    
    // 確保數值類型正確
    if (isset($data['Amt'])) {
        $data['Amt'] = (int) $data['Amt'];
    }
    
    // 確保字串長度符合規範
    if (isset($data['ItemDesc']) && mb_strlen($data['ItemDesc']) > 50) {
        $data['ItemDesc'] = mb_substr($data['ItemDesc'], 0, 50);
    }
    
    return $data;
}
```

### 解決回調問題

**任務描述**: 修復支付回調處理問題

**實作範例**:

```php
public function handle_callback() {
    try {
        // 驗證來源
        $this->validate_callback_source();
        
        // 取得回調資料
        $callback_data = $this->get_callback_data();
        
        // 驗證簽章
        if (!$this->verify_callback_signature($callback_data)) {
            throw new Exception('簽章驗證失敗');
        }
        
        // 處理回調
        $this->process_callback($callback_data);
        
        // 回應成功
        echo 'OK';
        exit;
        
    } catch (Exception $e) {
        $this->logger->error('回調處理失敗', array(
            'error' => $e->getMessage(),
            'data' => $_POST
        ));
        
        http_response_code(400);
        echo 'ERROR: ' . $e->getMessage();
        exit;
    }
}

private function validate_callback_source() {
    $allowed_ips = array(
        '61.61.86.202',
        '61.61.86.203',
        // 藍新金流的 IP 範圍
    );
    
    $client_ip = $this->get_client_ip();
    
    if (!in_array($client_ip, $allowed_ips)) {
        throw new Exception('來源 IP 不在允許範圍內');
    }
}
```

---

## 🌐 現代化任務 (v1.0.10+)

### WooCommerce Blocks 整合

**任務描述**: 確保支付閘道在 WooCommerce 新式區塊結帳中正常運作

**涉及檔案**:
- `includes/class-newebpay-wc-blocks.php`
- `assets/js/blocks-checkout.js`
- `Central.php`

**實作重點**:

1. **相容性聲明**:
```php
// 在 Central.php 中
\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 
    'cart_checkout_blocks', 
    __FILE__, 
    true 
);
```

2. **區塊整合類別**:
```php
// includes/class-newebpay-wc-blocks.php
class Newebpay_WooCommerce_Blocks_Integration {
    public function register_payment_method_type($payment_method_registry) {
        $payment_method_registry->register(
            new Newebpay_Blocks_Payment_Method()
        );
    }
}
```

3. **前端 JavaScript 支援**:
```javascript
// assets/js/blocks-checkout.js
const NewebpayPaymentMethod = {
    name: 'newebpay',
    label: decodeEntities(settings.title),
    content: React.createElement(NewebpayComponent),
    edit: React.createElement(NewebpayComponent),
    canMakePayment: () => true,
    ariaLabel: decodeEntities(settings.title),
    supports: {
        features: settings.supports,
    },
};
```

### 多語言本地化

**任務描述**: 實作完整的多語言支援

**涉及檔案**:
- `Central.php` - 載入翻譯
- `languages/` - 翻譯檔案
- 所有包含使用者文字的 PHP 檔案

**實作步驟**:

1. **設定 Text Domain**:
```php
// Central.php plugin header
 * Text Domain: newebpay-payment
 * Domain Path: /languages
```

2. **載入翻譯**:
```php
// Central.php
private function load_textdomain() {
    load_plugin_textdomain( 
        'newebpay-payment', 
        false, 
        dirname( plugin_basename( __FILE__ ) ) . '/languages/' 
    );
}
```

3. **文字國際化**:
```php
// 所有使用者可見文字
echo __('藍新金流', 'newebpay-payment');
$message = __('付款成功', 'newebpay-payment');
```

4. **翻譯檔案結構**:
```
languages/
├── newebpay-payment.pot         # 翻譯模板
├── newebpay-payment-zh_TW.po    # 繁體中文翻譯源檔
└── newebpay-payment-zh_TW.mo    # 繁體中文編譯檔
```

### 相容性升級

**任務描述**: 升級插件以支援最新的 WordPress 和 WooCommerce 標準

**關鍵更新點**:

1. **HPOS 支援**:
```php
\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 
    'custom_order_tables', 
    __FILE__, 
    true 
);
```

2. **現代 PHP 語法**:
- 使用 `wp_remote_post()` 取代 `curl`
- 適當的資料驗證和清理
- 遵循 WordPress Coding Standards

3. **安全性強化**:
```php
// 輸入驗證
$order_id = absint($_POST['order_id']);
$hash = sanitize_text_field($_POST['hash']);

// 輸出轉義
echo esc_html($message);
echo esc_attr($value);
```

**測試檢查清單**:
- [ ] 傳統結帳頁面正常運作
- [ ] 區塊結帳頁面正常運作  
- [ ] 多語言切換正確顯示
- [ ] 所有支付方式功能完整
- [ ] 後台設定介面翻譯完整
- [ ] 錯誤訊息正確本地化

這些範例為 AI 提供了具體的實作參考，可以快速理解如何處理各種開發任務。
