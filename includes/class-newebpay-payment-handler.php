<?php
/**
 * 藍新金流付款處理器
 * 
 * 負責處理付款相關的核心邏輯，包括：
 * - 付款參數生成
 * - 付款方式驗證
 * - 付款流程控制
 * 
 * @package Newebpay_Payment
 * @since 1.0.10
 */

if (!defined('ABSPATH')) {
    exit;
}

class Newebpay_Payment_Handler
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
     * 取得藍新金流付款參數
     * 
     * @param WC_Order $order 訂單物件
     * @return array 付款參數陣列
     * @throws InvalidArgumentException 當參數驗證失敗時
     */
    public function get_payment_args($order)
    {
        $merchant_order_no = $order->get_id() . 'T' . time(); // 防止重複
        $order->update_meta_data('_newebpayMerchantOrderNo', $merchant_order_no);
        $order->save();
        
        $post_data = array(
            'MerchantID'      => $this->gateway->MerchantID,
            'RespondType'     => 'JSON',
            'TimeStamp'       => time(),
            'Version'         => '2.3',
            'MerchantOrderNo' => $merchant_order_no,
            'Amt'             => round($order->get_total()),
            'ItemDesc'        => $this->generate_item_description($order),
            'ExpireDate'      => date('Ymd', time() + intval($this->gateway->ExpireDate) * 24 * 60 * 60),
            'Email'           => $order->get_billing_email(),
            'LoginType'       => '0',
            'NotifyURL'       => $this->gateway->notify_url,
            'ReturnURL'       => $this->gateway->get_return_url($order),
            'ClientBackURL'   => wc_get_cart_url(),
            'CustomerURL'     => $this->gateway->get_return_url($order),
            'LangType'        => $this->gateway->LangType,
        );
        
        // 使用驗證器驗證付款資料
        try {
            $post_data = Newebpay_Validator::validate_payment_data($post_data);
        } catch (InvalidArgumentException $e) {
            Newebpay_Error_Handler::handle_validation_error($e->getMessage());
            throw $e;
        }

        // 處理付款方式選擇
        $selected_payment = $this->get_selected_payment_method($order);
        $this->apply_payment_method($post_data, $selected_payment);
        
        // 處理超商取貨設定
        $this->apply_cvscom_settings($post_data, $order);

        // 加密處理
        $aes = $this->gateway->encProcess->create_mpg_aes_encrypt($post_data, $this->gateway->HashKey, $this->gateway->HashIV);
        $sha256 = $this->gateway->encProcess->aes_sha256_str($aes, $this->gateway->HashKey, $this->gateway->HashIV);

        return array(
            'MerchantID'  => $this->gateway->MerchantID,
            'TradeInfo'   => $aes,
            'TradeSha'    => $sha256,
            'Version'     => '2.3',
            'CartVersion' => 'New_WooCommerce_1_0_1',
        );
    }

    /**
     * 取得選擇的付款方式
     * 
     * @param WC_Order $order 訂單物件
     * @return string 選擇的付款方式
     */
    private function get_selected_payment_method($order)
    {
        $selected_payment = '';
        
        // 1. 檢查當前類別中的選擇
        if (!empty($this->gateway->nwpSelectedPayment)) {
            $selected_payment = $this->gateway->nwpSelectedPayment;
        }
        
        // 2. 從訂單 meta 資料取得
        if (empty($selected_payment)) {
            $selected_payment = $order->get_meta('_nwpSelectedPayment');
        }
        
        // 3. 從 $_POST 中取得
        if (empty($selected_payment)) {
            $post_methods = array('selectedmethod', 'newebpay_selected_method', 'nwp_selected_payments');
            foreach ($post_methods as $method) {
                if (!empty($_POST[$method])) {
                    $selected_payment = sanitize_text_field($_POST[$method]);
                    $this->gateway->nwpSelectedPayment = $selected_payment;
                    $order->update_meta_data('_nwpSelectedPayment', $selected_payment);
                    $order->save();
                    break;
                }
            }
        }
        
        // 驗證付款方式是否有效
        $get_select_payment = $this->get_available_payment_methods();
        $is_valid_payment = $this->validate_payment_method($selected_payment, $get_select_payment);
        
        // 如果無效，使用預設付款方式
        if (!$is_valid_payment) {
            $selected_payment = $this->get_default_payment_method($get_select_payment);
        }
        
        // 記錄使用的付款方式
        if (!empty($selected_payment)) {
            $order->add_order_note("使用支付方式: " . $selected_payment, 0);
        }
        
        return $selected_payment;
    }

    /**
     * 套用付款方式到參數
     * 
     * @param array $post_data 付款參數
     * @param string $selected_payment 選擇的付款方式
     */
    private function apply_payment_method(&$post_data, $selected_payment)
    {
        if (empty($selected_payment)) {
            return;
        }

        // 智慧ATM2.0 特殊處理 - 使用 VACC 參數加上額外參數
        if ($selected_payment === 'SmartPay') {
            $post_data['VACC'] = 1;
            
            // 取得智慧ATM2.0的設定參數
            $source_type = trim($this->gateway->get_option('SmartPaySourceType'));
            $source_bank_id = trim($this->gateway->get_option('SmartPaySourceBankId'));
            $source_account_no = trim($this->gateway->get_option('SmartPaySourceAccountNo'));
            
            // 加入智慧ATM2.0必要參數
            if (!empty($source_type)) {
                $post_data['SourceType'] = $source_type;
            }
            if (!empty($source_bank_id)) {
                $post_data['SourceBankId'] = $source_bank_id;
            }
            if (!empty($source_account_no)) {
                $post_data['SourceAccountNo'] = $source_account_no;
            }
        } elseif ($selected_payment === 'CVSCOMPayed') {
            // 超商取貨付款
            $post_data['CVSCOM'] = '2';
        } elseif ($selected_payment === 'CVSCOMNotPayed') {
            // 超商取貨不付款
            $post_data['CVSCOM'] = '1';
        } else {
            // 其他付款方式的正常處理
            $post_data[strtoupper($selected_payment)] = 1;
        }
    }

    /**
     * 套用超商取貨設定
     * 
     * @param array $post_data 付款參數
     * @param WC_Order $order 訂單物件
     */
    private function apply_cvscom_settings(&$post_data, $order)
    {
        // 超商取貨的備用邏輯（如果上面的 selected_payment 沒有處理到）
        if (empty($post_data['CVSCOM'])) {
            $get_select_payment = $this->get_available_payment_methods();
            $cvscom_payed = $get_select_payment['CVSCOMPayed'] ?? '';
            $cvscom_not_payed = $get_select_payment['CVSCOMNotPayed'] ?? '';
            $custom_cvscom_not_payed = $order->get_meta('_CVSCOMNotPayed') ?? '';

            if ($custom_cvscom_not_payed == '1' && $cvscom_not_payed == '1') {
                // 使用者選擇了超商取貨不付款
                $post_data['CVSCOM'] = '1';
            } elseif ($cvscom_payed == '1') {
                // 使用者選擇了超商取貨付款
                $post_data['CVSCOM'] = '2';
            }
        }
    }

    /**
     * 驗證付款方式是否有效
     * 
     * @param string $selected_payment 選擇的付款方式
     * @param array $available_methods 可用的付款方式
     * @return bool 是否有效
     */
    private function validate_payment_method($selected_payment, $available_methods)
    {
        if (empty($selected_payment)) {
            return false;
        }

        $payment_config_map = array(
            'credit' => 'Credit',
            'webatm' => 'Webatm',
            'vacc' => 'Vacc',
            'cvs' => 'CVS',
            'barcode' => 'BARCODE',
            'linepay' => 'LinePay',
            'esunwallet' => 'EsunWallet',
            'taiwanpay' => 'TaiwanPay',
            'androidpay' => 'AndroidPay',
            'samsungpay' => 'SamsungPay',
            'applepay' => 'APPLEPAY',
            'smartpay' => 'SmartPay'
        );
        
        $payment_config_key = $payment_config_map[strtolower($selected_payment)] ?? strtoupper($selected_payment);
        return isset($available_methods[$payment_config_key]) && $available_methods[$payment_config_key] == '1';
    }

    /**
     * 取得預設付款方式
     * 
     * @param array $available_methods 可用的付款方式
     * @return string 預設付款方式
     */
    private function get_default_payment_method($available_methods)
    {
        $payment_priority = array('Credit', 'AndroidPay', 'SamsungPay', 'LinePay', 'EsunWallet', 'TaiwanPay', 'Webatm', 'Vacc', 'CVS', 'BARCODE');
        
        foreach ($payment_priority as $payment_method) {
            if (isset($available_methods[$payment_method]) && $available_methods[$payment_method] == '1') {
                return strtolower($payment_method);
            }
        }
        
        return '';
    }

    /**
     * 取得可用的付款方式
     * 
     * @return array 可用的付款方式陣列
     */
    private function get_available_payment_methods()
    {
        $payment_method = array();

        foreach ($this->gateway->settings as $row => $value) {
            if (str_contains($row, 'NwpPaymentMethod') && $value == 'yes') {
                $payment_method[str_replace('NwpPaymentMethod', '', $row)] = 1;
            }
        }

        return $payment_method;
    }

    /**
     * 生成商品描述
     * 
     * @param WC_Order $order 訂單物件
     * @return string 商品描述
     */
    private function generate_item_description($order)
    {
        if (!isset($order)) {
            return '';
        }
        
        $item_name = $order->get_items();
        $item_cnt = 1;
        $itemdesc = '';
        
        foreach ($item_name as $item_value) {
            if ($item_cnt != count($item_name)) {
                $itemdesc .= $item_value->get_name() . ' x ' . $item_value->get_quantity() . '，';
            } elseif ($item_cnt == count($item_name)) {
                $itemdesc .= $item_value->get_name() . ' x ' . $item_value->get_quantity();
            }
            $item_cnt++;
        }
        
        return $itemdesc;
    }

    /**
     * 處理付款
     * 
     * @param int $order_id 訂單 ID
     * @return array 處理結果
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        
        // 設定超商取貨選項
        if ($this->gateway->nwpCVSCOMNotPayed == 1) {
            $order->update_meta_data('_CVSCOMNotPayed', 1);
        } else {
            $order->update_meta_data('_CVSCOMNotPayed', 0);
        }
        
        // 儲存選擇的付款方式
        $order->update_meta_data('_nwpSelectedPayment', $this->gateway->nwpSelectedPayment);
        $order->save();
        $order->add_order_note($this->gateway->nwpSelectedPayment, 1);
        
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
    }
}
