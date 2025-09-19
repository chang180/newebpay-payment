<?php
/**
 * 藍新金流表單處理器
 * 
 * 負責處理表單和 UI 相關的邏輯，包括：
 * - 付款表單生成
 * - 付款方式選擇
 * - 表單驗證
 * 
 * @package Newebpay_Payment
 * @since 1.0.10
 */

if (!defined('ABSPATH')) {
    exit;
}

class Newebpay_Form_Handler
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
     * 顯示付款表單
     * 
     * @return void
     */
    public function display_payment_fields()
    {
        echo $this->get_payment_form_html();
    }

    /**
     * 取得付款表單 HTML
     * 
     * @return string 付款表單 HTML
     */
    private function get_payment_form_html()
    {
        $payment_method = $this->get_available_payment_methods();
        $cvscom_not_payed = '';
        $szHtml = '';
        $szHtml .= '付款方式 : ';
        $szHtml .= '<select name="nwp_selected_payments">';

        // 檢查是否為重新付款頁面
        $is_order_pay_page = false;
        $current_order = null;
        
        if (is_wc_endpoint_url('order-pay')) {
            $is_order_pay_page = true;
            
            // 嘗試獲取當前訂單
            global $wp;
            if (isset($wp->query_vars['order-pay'])) {
                $order_id = absint($wp->query_vars['order-pay']);
                $current_order = wc_get_order($order_id);
            }
        }

        // Debug: 記錄重新付款頁面的處理
        if ($is_order_pay_page && defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('Newebpay: Order-pay page detected. Order ID: ' . ($current_order ? $current_order->get_id() : 'unknown'));
            error_log('Newebpay: Available payment methods: ' . print_r(array_keys($payment_method), true));
        }

        foreach ($payment_method as $payment_method_key => $value) {
            if ($payment_method_key == 'CVSCOMNotPayed') {
                $cvscom_not_payed = 1;
                continue;
            }
            
            // 只在測試模式下限制微信和支付寶
            if ($this->gateway->settings['TestMode'] == 'yes' && in_array($payment_method_key, array('EZPWECHAT', 'EZPALIPAY'))) {
                continue;
            }

            $szHtml .= '<option value="' . esc_attr($payment_method_key) . '">';
            $szHtml .= esc_html($this->convert_payment_type($payment_method_key));
            $szHtml .= '</option>';
        }
        $szHtml .= '</select>';
        
        if ($cvscom_not_payed == 1) {
            $szHtml .= '<br><input type="checkbox" name="cvscom_not_payed" id="CVSCOMNotPayed" value="CVSCOMNotPayed">';
            $szHtml .= '<label for="CVSCOMNotPayed">' . __('超商取貨不付款', 'newebpay-payment') . '</label>';
        }
        
        return $szHtml;
    }

    /**
     * 取得可用的付款方式
     * 
     * @return array 可用的付款方式陣列
     */
    private function get_available_payment_methods()
    {
        $payment_method = array();

        // Debug: 記錄所有設定
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('Newebpay Settings for payment selection:');
            foreach ($this->gateway->settings as $key => $value) {
                if (strpos($key, 'Method') !== false || in_array($key, array('CREDIT', 'WEBATM', 'VACC', 'CVS', 'BARCODE'))) {
                    error_log("  {$key} = {$value}");
                }
            }
        }

        foreach ($this->gateway->settings as $row => $value) {
            if (str_contains($row, 'NwpPaymentMethod') && $value == 'yes') {
                $payment_method[str_replace('NwpPaymentMethod', '', $row)] = 1;
            }
        }

        // Debug: 記錄找到的付款方式
        if (defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('Newebpay: Found payment methods: ' . print_r($payment_method, true));
        }

        return $payment_method;
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
            'CVSCOMNotPayed' => '超商取貨不付款',
        );

        return $method[$payment_method] ?? $payment_method;
    }

    /**
     * 驗證表單欄位
     * 
     * @return bool 驗證結果
     */
    public function validate_fields()
    {
        // 支援傳統表單和 WooCommerce Blocks 的參數名稱
        $cvscom_not_payed = sanitize_text_field($_POST['cvscom_not_payed'] ?? '');
        
        // 檢查 WooCommerce Blocks 傳來的 cvscom 資料
        if (empty($cvscom_not_payed) && isset($_POST['cvscom_not_payed_blocks'])) {
            $cvscom_not_payed = sanitize_text_field($_POST['cvscom_not_payed_blocks']);
        }
        
        if ($cvscom_not_payed == 'CVSCOMNotPayed' || $cvscom_not_payed == 'true' || $cvscom_not_payed == '1') {
            $this->gateway->nwpCVSCOMNotPayed = 1;
        } else {
            $this->gateway->nwpCVSCOMNotPayed = 0;
        }
        
        // 支援傳統表單和 WooCommerce Blocks 的付款方式選擇
        $choose_payment = sanitize_text_field($_POST['nwp_selected_payments'] ?? '');
        
        // 檢查 WooCommerce Blocks 傳來的付款方式
        if (empty($choose_payment) && isset($_POST['newebpay_selected_method'])) {
            $choose_payment = sanitize_text_field($_POST['newebpay_selected_method']);
        }
        
        // 也檢查 session storage 或其他可能的來源
        if (empty($choose_payment) && isset($_POST['newebpay_wc_selected_method'])) {
            $choose_payment = sanitize_text_field($_POST['newebpay_wc_selected_method']);
        }
        
        // 也檢查 selectedmethod 欄位（WooCommerce Blocks 使用）
        if (empty($choose_payment) && isset($_POST['selectedmethod'])) {
            $choose_payment = sanitize_text_field($_POST['selectedmethod']);
        }
        
        // 智慧ATM2.0 特殊處理：WooCommerce Blocks 可能傳送 smartpay，需要轉換為 SmartPay
        if ($choose_payment === 'smartpay') {
            $choose_payment = 'SmartPay';
        }
        
        // 檢查是否是 newebpay 付款方式
        $is_newebpay_payment = false;
        
        // 傳統方式：檢查 $_POST['payment_method']
        if (isset($_POST['payment_method']) && $_POST['payment_method'] == $this->gateway->id) {
            $is_newebpay_payment = true;
        }
        
        // WooCommerce Blocks 方式：檢查是否有 newebpay 相關參數
        if (!$is_newebpay_payment && (
            isset($_POST['selectedmethod']) || 
            isset($_POST['newebpay_selected_method']) || 
            isset($_POST['nwp_selected_payments'])
        )) {
            $is_newebpay_payment = true;
            // 為了確保後續處理正確，設置 payment_method
            $_POST['payment_method'] = $this->gateway->id;
        }
        
        if ($is_newebpay_payment) {
            $this->gateway->nwpSelectedPayment = $choose_payment;
            return true;
        } else {
            return false;
        }
    }

    /**
     * 生成藍新金流表單
     * 
     * @param int $order_id 訂單 ID
     * @return string 表單 HTML
     */
    public function generate_payment_form($order_id)
    {
        $order = wc_get_order($order_id);
        // 使用反射來調用私有方法
        $reflection = new ReflectionClass($this->gateway);
        $method = $reflection->getMethod('get_newebpay_args');
        $method->setAccessible(true);
        $newebpay_args = $method->invoke($this->gateway, $order);
        $newebpay_gateway = $this->gateway->gateway;
        $newebpay_args_array = array();
        
        foreach ($newebpay_args as $key => $value) {
            $newebpay_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        }

        return '<form id="newebpay" name="newebpay" action="' . $newebpay_gateway . '" method="post" target="_top">' . implode('', $newebpay_args_array) . '
            <input type="submit" class="button-alt" id="submit_newebpay_payment_form" value="' . __('前往 藍新金流 支付頁面', 'newebpay') . '" />
            </form>' . "<script>setTimeout(\"document.forms['newebpay'].submit();\",\"10000\")</script>";
    }

    /**
     * 顯示收據頁面
     * 
     * @param WC_Order|int $order 訂單物件或訂單 ID
     * @return void
     */
    public function display_receipt_page($order)
    {
        echo '<p>' . __('10秒後會自動跳轉到藍新金流支付頁面，或者按下方按鈕直接前往<br>', 'newebpay-payment') . '</p>';
        
        // 處理訂單參數，可能是物件或 ID
        if (is_numeric($order)) {
            $order_id = $order;
        } elseif (is_object($order) && method_exists($order, 'get_id')) {
            $order_id = $order->get_id();
        } else {
            error_log('Newebpay: Invalid order parameter in display_receipt_page');
            return;
        }
        
        echo $this->generate_payment_form($order_id);
    }

    /**
     * 控制重試付款按鈕的顯示
     * 
     * @param array $actions 動作陣列
     * @param WC_Order $order 訂單物件
     * @return array 過濾後的動作陣列
     */
    public function filter_retry_payment_actions($actions, $order)
    {
        // 只處理藍新金流的訂單
        if ($order->get_payment_method() !== 'newebpay') {
            return $actions;
        }
        
        // 如果訂單已經有 transaction_id（表示曾經付款成功），移除重試選項
        if (!empty($order->get_transaction_id())) {
            unset($actions['pay']);
            return $actions;
        }
        
        // 如果訂單狀態不是 failed 或 pending（需要付款的狀態），移除重試選項
        if (!in_array($order->get_status(), array('failed', 'pending'))) {
            unset($actions['pay']);
            return $actions;
        }
        
        // 只有當訂單狀態為 failed 且沒有 transaction_id 時，才顯示重試按鈕
        return $actions;
    }
    
    /**
     * 控制訂單是否需要付款
     * 
     * @param bool $needs_payment 是否需要付款
     * @param WC_Order $order 訂單物件
     * @param array $valid_statuses 有效狀態陣列
     * @return bool 是否需要付款
     */
    public function filter_order_needs_payment($needs_payment, $order, $valid_statuses)
    {
        // 只處理藍新金流的訂單
        if ($order->get_payment_method() !== 'newebpay') {
            return $needs_payment;
        }
        
        // 只有在訂單已經成功付款的情況下才阻止重新付款
        if (!empty($order->get_transaction_id()) && 
            in_array($order->get_status(), array('processing', 'completed'))) {
            return false;
        }
        
        return $needs_payment;
    }
    
    /**
     * 控制付款完成的有效狀態
     * 
     * @param array $statuses 狀態陣列
     * @param WC_Order $order 訂單物件
     * @return array 過濾後的狀態陣列
     */
    public function filter_payment_complete_statuses($statuses, $order)
    {
        // 只處理藍新金流的訂單
        if ($order && $order->get_payment_method() === 'newebpay') {
            // 確保 processing 和 completed 狀態被認為是付款完成狀態
            if (!in_array('processing', $statuses)) {
                $statuses[] = 'processing';
            }
            if (!in_array('completed', $statuses)) {
                $statuses[] = 'completed';
            }
        }
        
        return $statuses;
    }

    /**
     * 添加自定義樣式來隱藏 WooCommerce Blocks 的預設重試按鈕
     * 
     * @return void
     */
    public function add_custom_failed_order_styles()
    {
        // 只在訂單確認頁面執行
        if (is_wc_endpoint_url('order-received')) {
            global $wp;
            
            // 嘗試獲取當前訂單
            $order = null;
            if (isset($wp->query_vars['order-received'])) {
                $order_id = absint($wp->query_vars['order-received']);
                $order = wc_get_order($order_id);
            }
            
            // 只對藍新金流的失敗訂單隱藏預設按鈕
            if ($order && 
                $order->get_payment_method() === 'newebpay' && 
                $order->get_status() === 'failed' &&
                empty($order->get_transaction_id())) {
                ?>
                <style type="text/css">
                    /* 隱藏 WooCommerce Blocks 預設的失敗訂單動作按鈕 */
                    .wc-block-order-confirmation-status__actions {
                        display: none !important;
                    }
                    
                    /* 確保我們自定義的重試按鈕顯示正常 */
                    .woocommerce-order-retry-payment {
                        display: block !important;
                    }
                </style>
                <?php
            }
        }
    }
}
