<?php
/**
 * 藍新金流 WooCommerce Blocks 處理器
 * 
 * 負責處理 WooCommerce Blocks 相關的邏輯，包括：
 * - Blocks 註冊
 * - Blocks 渲染
 * - Blocks 資料處理
 * 
 * @package Newebpay_Payment
 * @since 1.0.10
 */

if (!defined('ABSPATH')) {
    exit;
}

class Newebpay_Blocks_Handler
{
    /**
     * 主支付閘道實例
     * @var WC_newebpay
     */
    private $gateway;

    /**
     * 建構函數
     * 
     * @param WC_newebpay $gateway 主支付閘道實例
     */
    public function __construct($gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * 註冊 WooCommerce Blocks
     * 
     * @return void
     */
    public function register_blocks()
    {
        // 檢查 WordPress 版本和 Blocks 支援
        if (!function_exists('register_block_type')) {
            return;
        }

        try {
            register_block_type('newebpay/payment', array(
                'render_callback' => array($this, 'render_payment_block'),
                'attributes' => array(
                    'payment_methods' => array(
                        'type' => 'array',
                        'default' => array(),
                    ),
                    'test_mode' => array(
                        'type' => 'boolean',
                        'default' => false,
                    ),
                ),
            ));
        } catch (Exception $e) {
            // 記錄錯誤但不中斷執行
            if (function_exists('wc_get_logger')) {
                $logger = wc_get_logger();
                $logger->error('Failed to register newebpay block', array(
                    'source' => 'newebpay-payment-blocks',
                    'error' => $e->getMessage()
                ));
            }
        }
    }

    /**
     * 渲染付款 Block
     * 
     * @param array $attributes Block 屬性
     * @param string $content Block 內容
     * @return string 渲染後的 HTML
     */
    public function render_payment_block($attributes, $content)
    {
        // 檢查是否啟用藍新金流
        if (!$this->gateway->is_available()) {
            return '';
        }

        $payment_methods = $this->get_available_payment_methods();
        if (empty($payment_methods)) {
            return '';
        }

        ob_start();
        ?>
        <div class="newebpay-payment-block">
            <h3><?php echo esc_html($this->gateway->method_title); ?></h3>
            <p><?php echo esc_html($this->gateway->method_description); ?></p>
            
            <div class="newebpay-payment-methods">
                <?php foreach ($payment_methods as $method_key => $method_name): ?>
                    <label class="newebpay-payment-method">
                        <input type="radio" 
                               name="newebpay_selected_method" 
                               value="<?php echo esc_attr($method_key); ?>"
                               <?php checked($method_key, 'Credit'); ?>>
                        <span class="payment-method-name"><?php echo esc_html($method_name); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <?php if ($this->has_cvscom_option()): ?>
                <div class="newebpay-cvscom-option">
                    <label>
                        <input type="checkbox" 
                               name="cvscom_not_payed_blocks" 
                               value="CVSCOMNotPayed">
                        <?php _e('超商取貨不付款', 'newebpay-payment'); ?>
                    </label>
                </div>
            <?php endif; ?>

            <div class="newebpay-payment-info">
                <p><small><?php _e('選擇付款方式後，將跳轉至藍新金流安全付款頁面', 'newebpay-payment'); ?></small></p>
            </div>
        </div>

        <style>
        .newebpay-payment-block {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin: 10px 0;
            background: #fff;
        }
        
        .newebpay-payment-methods {
            margin: 15px 0;
        }
        
        .newebpay-payment-method {
            display: block;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .newebpay-payment-method:hover {
            background: #f9f9f9;
            border-color: #0073aa;
        }
        
        .newebpay-payment-method input[type="radio"] {
            margin-right: 10px;
        }
        
        .newebpay-cvscom-option {
            margin: 15px 0;
            padding: 10px;
            background: #f0f8ff;
            border-radius: 3px;
        }
        
        .newebpay-payment-info {
            margin-top: 15px;
            padding: 10px;
            background: #f9f9f9;
            border-radius: 3px;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * 取得可用的付款方式
     * 
     * @return array 付款方式陣列
     */
    private function get_available_payment_methods()
    {
        $payment_methods = array();
        $available_methods = $this->get_available_payment_methods_from_settings();

        foreach ($available_methods as $method_key => $enabled) {
            if ($enabled) {
                $payment_methods[$method_key] = $this->convert_payment_type($method_key);
            }
        }

        return $payment_methods;
    }

    /**
     * 從設定中取得可用的付款方式
     * 
     * @return array 付款方式設定
     */
    private function get_available_payment_methods_from_settings()
    {
        $payment_methods = array();

        foreach ($this->gateway->settings as $key => $value) {
            if (str_contains($key, 'NwpPaymentMethod') && $value == 'yes') {
                $method_key = str_replace('NwpPaymentMethod', '', $key);
                $payment_methods[$method_key] = 1;
            }
        }

        return $payment_methods;
    }

    /**
     * 轉換付款類型名稱
     * 
     * @param string $payment_method 付款方式
     * @return string 轉換後的付款方式名稱
     */
    private function convert_payment_type($payment_method)
    {
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
        );

        return $method[$payment_method] ?? $payment_method;
    }

    /**
     * 檢查是否有超商取貨選項
     * 
     * @return bool 是否有超商取貨選項
     */
    private function has_cvscom_option()
    {
        $available_methods = $this->get_available_payment_methods_from_settings();
        return isset($available_methods['CVSCOMNotPayed']) && $available_methods['CVSCOMNotPayed'] == 1;
    }

    /**
     * 處理 Blocks 付款資料
     * 
     * @param array $payment_data 付款資料
     * @return array 處理後的付款資料
     */
    public function process_blocks_payment_data($payment_data)
    {
        // 處理付款方式選擇
        if (isset($payment_data['newebpay_selected_method'])) {
            $selected_method = sanitize_text_field($payment_data['newebpay_selected_method']);
            
            // 智慧ATM2.0 特殊處理
            if ($selected_method === 'smartpay') {
                $selected_method = 'SmartPay';
            }
            
            $payment_data['nwp_selected_payments'] = $selected_method;
            $payment_data['selectedmethod'] = $selected_method;
        }

        // 處理超商取貨選項
        if (isset($payment_data['cvscom_not_payed_blocks'])) {
            $payment_data['cvscom_not_payed'] = sanitize_text_field($payment_data['cvscom_not_payed_blocks']);
        }

        return $payment_data;
    }

    /**
     * 處理 Blocks 付款設定
     * 
     * @param array $payment_method_data 付款方式資料
     * @return array 處理後的付款方式資料
     */
    public function process_blocks_payment_setup($payment_method_data)
    {
        // 確保付款方式資料包含必要的資訊
        $payment_method_data['newebpay_selected_method'] = $payment_method_data['newebpay_selected_method'] ?? '';
        $payment_method_data['cvscom_not_payed_blocks'] = $payment_method_data['cvscom_not_payed_blocks'] ?? '';

        return $payment_method_data;
    }

    /**
     * 取得 Blocks 支援的付款方式
     * 
     * @return array 支援的付款方式
     */
    public function get_blocks_supported_payment_methods()
    {
        return array(
            'Credit' => __('信用卡', 'newebpay-payment'),
            'Webatm' => __('WebATM', 'newebpay-payment'),
            'Vacc' => __('ATM轉帳', 'newebpay-payment'),
            'CVS' => __('超商代碼', 'newebpay-payment'),
            'BARCODE' => __('超商條碼', 'newebpay-payment'),
            'LinePay' => __('Line Pay', 'newebpay-payment'),
            'EsunWallet' => __('玉山 Wallet', 'newebpay-payment'),
            'TaiwanPay' => __('台灣 Pay', 'newebpay-payment'),
            'AndroidPay' => __('Google Pay', 'newebpay-payment'),
            'SamsungPay' => __('Samsung Pay', 'newebpay-payment'),
            'APPLEPAY' => __('Apple Pay', 'newebpay-payment'),
            'SmartPay' => __('智慧ATM2.0', 'newebpay-payment'),
        );
    }

    /**
     * 檢查 Blocks 是否可用
     * 
     * @return bool 是否可用
     */
    public function is_blocks_available()
    {
        // 檢查 WooCommerce Blocks 是否啟用
        if (!class_exists('Automattic\WooCommerce\Blocks\Package')) {
            return false;
        }

        // 檢查藍新金流是否啟用
        if (!$this->gateway->is_available()) {
            return false;
        }

        return true;
    }

    /**
     * 取得 Blocks 設定
     * 
     * @return array Blocks 設定
     */
    public function get_blocks_settings()
    {
        return array(
            'title' => $this->gateway->method_title,
            'description' => $this->gateway->method_description,
            'icon' => $this->gateway->icon,
            'supports' => $this->gateway->supports,
            'available_payment_methods' => $this->get_available_payment_methods(),
            'has_cvscom_option' => $this->has_cvscom_option(),
            'test_mode' => $this->gateway->TestMode === 'yes',
        );
    }

    /**
     * 處理 Blocks 錯誤
     * 
     * @param string $error_message 錯誤訊息
     * @param string $error_code 錯誤代碼
     * @return void
     */
    public function handle_blocks_error($error_message, $error_code = '')
    {
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->error('Blocks payment error', array(
                'source' => 'newebpay-payment-blocks',
                'error_message' => $error_message,
                'error_code' => $error_code,
            ));
        }
    }

    /**
     * 取得 Blocks 付款表單 HTML
     * 
     * @param array $attributes Block 屬性
     * @return string 表單 HTML
     */
    public function get_blocks_payment_form_html($attributes = array())
    {
        if (!$this->is_blocks_available()) {
            return '';
        }

        $settings = $this->get_blocks_settings();
        
        ob_start();
        ?>
        <div class="newebpay-blocks-payment-form" data-settings="<?php echo esc_attr(json_encode($settings)); ?>">
            <?php echo $this->render_payment_block($attributes, ''); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
