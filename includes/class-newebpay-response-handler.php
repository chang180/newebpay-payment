<?php
/**
 * 藍新金流回應處理器
 * 
 * 負責處理付款回應相關的邏輯，包括：
 * - 付款成功/失敗處理
 * - 訂單狀態更新
 * - 用戶介面回應
 * 
 * @package Newebpay_Payment
 * @since 1.0.10
 */

if (!defined('ABSPATH')) {
    exit;
}

class Newebpay_Response_Handler
{
    /**
     * 主支付閘道實例
     * @var WC_newebpay
     */
    private $gateway;

    /**
     * 購物車管理器
     * @var Newebpay_Cart_Manager
     */
    private $cart_manager;

    /**
     * 建構函數
     * 
     * @param WC_newebpay $gateway 主支付閘道實例
     */
    public function __construct($gateway)
    {
        $this->gateway = $gateway;
        $this->cart_manager = Newebpay_Cart_Manager::get_instance();
    }

    /**
     * 處理訂單接收文字
     * 
     * @return string 回應文字
     */
    public function handle_order_received_text()
    {
        $req_data = array();

        // 防止其他付款方式顯示此文字
        if (!isset($_REQUEST['TradeSha'])) {
            return;
        }

        if (!empty(sanitize_text_field($_REQUEST['TradeSha']))) {
            if (!$this->chkShaIsVaildByReturnData($_REQUEST)) {
                Newebpay_Error_Handler::handle_security_error(__('SHA 驗證失敗', 'newebpay-payment'));
                echo __('請重新填單', 'newebpay-payment');
                exit();
            }
            $req_data = $this->gateway->encProcess->create_aes_decrypt(
                sanitize_text_field($_REQUEST['TradeInfo']),
                $this->gateway->HashKey,
                $this->gateway->HashIV
            );
        }

        // 初始化資料避免 NOTICE
        $init_indexes = 'Status,Message,TradeNo,MerchantOrderNo,PaymentType,P2GPaymentType,BankCode,CodeNo,Barcode_1,Barcode_2,Barcode_3,ExpireDate,CVSCOMName,StoreName,StoreAddr,CVSCOMPhone';
        $req_data = $this->init_array_data($req_data, $init_indexes);

        if (!empty($req_data['MerchantOrderNo']) && sanitize_text_field($_GET['key']) != '' && preg_match('/^wc_order_/', sanitize_text_field($_GET['key']))) {
            $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_GET['key']));
            $order = wc_get_order($order_id);
        }

        if (empty($order)) {
            return '交易失敗，請重新填單';
            exit();
        }

        // 處理付款結果
        $result = $this->process_payment_result($req_data, $order);
        
        // 處理自動登入
        $this->handle_auto_login($order, $req_data);
        
        // 處理重試付款按鈕
        $result .= $this->handle_retry_payment_button($order, $req_data);
        
        // 處理已付款訂單的按鈕隱藏
        $this->handle_paid_order_buttons($order);

        return $result;
    }

    /**
     * 處理付款結果
     * 
     * @param array $req_data 請求資料
     * @param WC_Order $order 訂單物件
     * @return string 處理結果文字
     */
    private function process_payment_result($req_data, $order)
    {
        $result = '';

        // 統一處理所有非成功狀態
        if (!empty($req_data['Status']) && $req_data['Status'] != 'SUCCESS') {
            $this->handle_payment_failure($order, $req_data);
            return ''; // 空的結果，後面會添加重試區塊
        } elseif (empty($req_data['PaymentType']) || empty($req_data['Status'])) {
            if (isset($order) && $order && $order->get_status() === 'failed') {
                return ''; // 空的結果，後面會添加重試區塊
            } else {
                return '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
            }
        } else {
            $result = '付款方式：' . esc_attr($this->get_payment_type_str($req_data['PaymentType'], !empty($req_data['P2GPaymentType']))) . '<br>';
        }

        // 根據付款類型處理
        switch ($req_data['PaymentType']) {
            case 'CREDIT':
            case 'WEBATM':
            case 'P2GEACC':
            case 'ACCLINK':
                $result .= $this->handle_instant_payment($req_data, $order);
                break;
            case 'VACC':
                $result .= $this->handle_virtual_account($req_data, $order);
                break;
            case 'CVS':
                $result .= $this->handle_cvs_payment($req_data, $order);
                break;
            case 'BARCODE':
                $result .= $this->handle_barcode_payment($req_data, $order);
                break;
            case 'CVSCOM':
                $result .= $this->handle_cvscom_payment($req_data, $order);
                break;
            default:
                $result .= $this->handle_other_payment($req_data, $order);
                break;
        }

        // 處理超商取貨資訊
        $result .= $this->handle_cvscom_info($req_data, $order);

        // 處理付款狀態
        if ($req_data['Status'] != 'SUCCESS') {
            $this->handle_payment_failure($order, $req_data);
        } else {
            $this->handle_payment_success($order, $req_data);
        }

        return $result;
    }

    /**
     * 處理即時付款
     * 
     * @param array $req_data 請求資料
     * @param WC_Order $order 訂單物件
     * @return string 處理結果
     */
    private function handle_instant_payment($req_data, $order)
    {
        if ($req_data['Status'] == 'SUCCESS') {
            return '交易成功<br>';
        }
        return '';
    }

    /**
     * 處理虛擬帳號
     * 
     * @param array $req_data 請求資料
     * @param WC_Order $order 訂單物件
     * @return string 處理結果
     */
    private function handle_virtual_account($req_data, $order)
    {
        if (!empty($req_data['BankCode']) && !empty($req_data['CodeNo'])) {
            $result = '取號成功<br>';
            $result .= '銀行代碼：' . esc_attr($req_data['BankCode']) . '<br>';
            $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
            return $result;
        } else {
            $this->handle_payment_failure($order, $req_data);
            return '';
        }
    }

    /**
     * 處理超商代碼
     * 
     * @param array $req_data 請求資料
     * @param WC_Order $order 訂單物件
     * @return string 處理結果
     */
    private function handle_cvs_payment($req_data, $order)
    {
        if (!empty($req_data['CodeNo'])) {
            $result = '取號成功<br>';
            $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
            return $result;
        } else {
            $this->handle_payment_failure($order, $req_data);
            return '';
        }
    }

    /**
     * 處理條碼付款
     * 
     * @param array $req_data 請求資料
     * @param WC_Order $order 訂單物件
     * @return string 處理結果
     */
    private function handle_barcode_payment($req_data, $order)
    {
        if (!empty($req_data['Barcode_1']) || !empty($req_data['Barcode_2']) || !empty($req_data['Barcode_3'])) {
            $result = '取號成功<br>';
            $result .= '請前往信箱列印繳費單<br>';
            return $result;
        } else {
            $this->handle_payment_failure($order, $req_data);
            return '';
        }
    }

    /**
     * 處理超商取貨
     * 
     * @param array $req_data 請求資料
     * @param WC_Order $order 訂單物件
     * @return string 處理結果
     */
    private function handle_cvscom_payment($req_data, $order)
    {
        if (empty($req_data['CVSCOMName']) || empty($req_data['StoreName']) || empty($req_data['StoreAddr'])) {
            return '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
        }
        return '';
    }

    /**
     * 處理其他付款方式
     * 
     * @param array $req_data 請求資料
     * @param WC_Order $order 訂單物件
     * @return string 處理結果
     */
    private function handle_other_payment($req_data, $order)
    {
        if ($req_data['Status'] == 'SUCCESS') {
            $result = '付款方式：' . esc_attr($req_data['PaymentType']) . '<br>';
            if (!empty($req_data['ExpireDate'])) {
                $result .= '非即時付款，詳細付款資訊請從信箱確認<br>';
            } else {
                $result .= '交易成功<br>';
            }
            $result .= '藍新金流交易序號：' . esc_attr($req_data['TradeNo']) . '<br>';
            return $result;
        }
        return '';
    }

    /**
     * 處理超商取貨資訊
     * 
     * @param array $req_data 請求資料
     * @param WC_Order $order 訂單物件
     * @return string 處理結果
     */
    private function handle_cvscom_info($req_data, $order)
    {
        if (!empty($req_data['CVSCOMName']) || !empty($req_data['StoreName']) || !empty($req_data['StoreAddr'])) {
            $order_id = (isset($order_id)) ? $order_id : $req_data['MerchantOrderNo'];
            $storeName = urldecode($req_data['StoreName']);
            $storeAddr = urldecode($req_data['StoreAddr']);
            $name = urldecode($req_data['CVSCOMName']);
            $phone = $req_data['CVSCOMPhone'];
            
            $result = '<br>取貨人：' . esc_attr($name) . '<br>電話：' . esc_attr($phone) . '<br>店家：' . esc_attr($storeName) . '<br>地址：' . esc_attr($storeAddr) . '<br>';
            $result .= '請等待超商通知取貨<br>';
            
            if (empty($order->get_meta('_newebpayStoreName'))) {
                $order->update_meta_data('_newebpayStoreName', $storeName);
                $order->update_meta_data('_newebpayStoreAddr', $storeAddr);
                $order->update_meta_data('_newebpayConsignee', $name);
                $order->update_meta_data('_newebpayConsigneePhone', $phone);
                $order->save();
            }
            
            return $result;
        }
        return '';
    }

    /**
     * 處理付款成功
     * 
     * @param WC_Order $order 訂單物件
     * @param array $req_data 請求資料
     */
    private function handle_payment_success($order, $req_data)
    {
        $previous_status = $order->get_status();

        // 設定交易編號
        $order->set_transaction_id($req_data['TradeNo']);
        $order->update_status('processing');

        // 如果訂單之前是失敗狀態，添加備註
        if ($previous_status === 'failed') {
            $order->add_order_note(__('Payment succeeded after previous failure - order recovered', 'newebpay-payment'));
        }

        // 付款成功時立即清空購物車
        if ($this->cart_manager) {
            $this->cart_manager->clear_cart_for_order($order->get_id());
        }
    }

    /**
     * 處理付款失敗
     * 
     * @param WC_Order $order 訂單物件
     * @param array $req_data 請求資料
     */
    private function handle_payment_failure($order, $req_data)
    {
        if ($order->is_paid()) {
            $order->add_order_note(__('Payment failed within paid order', 'newebpay-payment'));
            $order->save();
        } else {
            // 清除 transaction_id 以確保可以重試付款
            $order->set_transaction_id('');
            $order->update_status('failed', sprintf(
                __('Payment failed: %1$s (%2$s)', 'newebpay-payment'),
                $req_data['Status'],
                $req_data['Message']
            ));
            
            // 添加調試日誌
            if (function_exists('wc_get_logger')) {
                $logger = wc_get_logger();
                $logger->info('Payment failed - transaction_id cleared, status set to failed', array(
                    'source' => 'newebpay-payment', 
                    'order_id' => $order->get_id(),
                    'transaction_id' => $order->get_transaction_id(),
                    'status' => $order->get_status()
                ));
            }
        }
    }

    /**
     * 處理自動登入
     * 
     * @param WC_Order $order 訂單物件
     * @param array $req_data 請求資料
     */
    private function handle_auto_login($order, $req_data)
    {
        // 自動登入處理
        if (!is_user_logged_in()) {
            $user_email = $order->get_billing_email();
            if (!empty($user_email)) {
                $user = get_user_by('email', $user_email);
                if ($user && !is_wp_error($user)) {
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID, true);
                    
                    $login_note = ($req_data['Status'] == 'SUCCESS') 
                        ? 'Customer automatically logged in after payment' 
                        : 'Customer automatically logged in after checkout';
                    $order->add_order_note(__($login_note, 'newebpay-payment'));
                }
            }
        } else {
            // 如果已經登入但訂單的用戶 ID 不一致，更新訂單的客戶 ID
            $current_user_id = get_current_user_id();
            $order_user_id = $order->get_user_id();
            
            if ($order_user_id == 0 && $current_user_id > 0) {
                $user_email = $order->get_billing_email();
                $current_user_email = wp_get_current_user()->user_email;
                
                if ($user_email === $current_user_email) {
                    $order->set_customer_id($current_user_id);
                    $order->save();
                    $link_note = ($req_data['Status'] == 'SUCCESS') 
                        ? 'Order linked to logged-in customer after payment' 
                        : 'Order linked to logged-in customer after checkout';
                    $order->add_order_note(__($link_note, 'newebpay-payment'));
                }
            }
        }
    }

    /**
     * 處理重試付款按鈕
     * 
     * @param WC_Order $order 訂單物件
     * @param array $req_data 請求資料
     * @return string 重試按鈕 HTML
     */
    private function handle_retry_payment_button($order, $req_data)
    {
        if (isset($order) && $order && 
            $order->get_status() === 'failed' && 
            empty($order->get_transaction_id())) {
            
            $checkout_payment_url = $order->get_checkout_payment_url();
            if ($checkout_payment_url) {
                $result = '<div class="woocommerce-order-retry-payment" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #e74c3c; border-radius: 5px; text-align: center;">';
                $result .= '<h4 style="color: #e74c3c; margin: 0 0 15px 0; font-size: 18px;">💳 付款失敗</h4>';
                $result .= '<p style="margin: 0 0 20px 0; color: #666;">您的付款沒有成功完成，請重新嘗試付款。</p>';
                $result .= '<a href="' . esc_url($checkout_payment_url) . '" class="button alt wc-retry-payment" style="background-color: #e74c3c; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; border: none; cursor: pointer; transition: all 0.3s ease;">🔄 再試一次付款</a>';
                $result .= '</div>';
                return $result;
            }
        }
        return '';
    }

    /**
     * 處理已付款訂單的按鈕隱藏
     * 
     * @param WC_Order $order 訂單物件
     */
    private function handle_paid_order_buttons($order)
    {
        if (isset($order) && $order && 
            !empty($order->get_transaction_id()) && 
            in_array($order->get_status(), array('processing', 'completed'))) {
            
            add_action('wp_footer', function() {
                ?>
                <script type="text/javascript">
                jQuery(document).ready(function($) {
                    // 隱藏所有可能的重試付款按鈕和連結
                    $(".woocommerce-order-actions .button:contains('重試')").hide();
                    $(".woocommerce-order-actions .button:contains('retry')").hide();
                    $(".woocommerce-order-actions .button:contains('再試一次')").hide();
                    $(".woocommerce-order-actions .button:contains('pay')").hide();
                    $(".woocommerce-order-actions .button:contains('付款')").hide();
                    $("a[href*='order-pay']").each(function() {
                        if ($(this).text().indexOf('重試') !== -1 || 
                            $(this).text().indexOf('retry') !== -1 || 
                            $(this).text().indexOf('再試一次') !== -1 ||
                            $(this).text().indexOf('pay') !== -1 ||
                            $(this).text().indexOf('付款') !== -1) {
                            $(this).hide();
                        }
                    });
                });
                </script>
                <?php
            });
        }
    }

    /**
     * 處理回調回應
     */
    public function handle_callback_response()
    {
        $req_data = array();

        // 檢查SHA值是否正確
        if (!empty(sanitize_text_field($_REQUEST['TradeSha']))) {
            if (!$this->chkShaIsVaildByReturnData($_REQUEST)) {
                Newebpay_Error_Handler::handle_security_error(__('SHA 驗證失敗', 'newebpay-payment'));
                return;
            }
            $req_data = $this->gateway->encProcess->create_aes_decrypt(
                sanitize_text_field($_REQUEST['TradeInfo']),
                $this->gateway->HashKey,
                $this->gateway->HashIV
            );
            if (!is_array($req_data)) {
                Newebpay_Error_Handler::handle_security_error(__('解密失敗', 'newebpay-payment'));
                return;
            }
        }

        // 初始化資料
        $init_indexes = 'Status,Message,TradeNo,MerchantOrderNo,PaymentType,P2GPaymentType,PayTime';
        $req_data = $this->init_array_data($req_data, $init_indexes);
        $re_MerchantOrderNo = trim($req_data['MerchantOrderNo']);
        $re_Status = sanitize_text_field($_REQUEST['Status']) != '' ? sanitize_text_field($_REQUEST['Status']) : null;
        $re_TradeNo = $req_data['TradeNo'];
        $re_Amt = $req_data['Amt'];

        $order = wc_get_order(explode("T", $re_MerchantOrderNo)[0]);
        if (!$order) {
            echo __('取得訂單失敗，訂單編號', 'newebpay-payment') . esc_attr($re_MerchantOrderNo);
            exit();
        }

        $Amt = round($order->get_total());
        if ($order->is_paid()) {
            echo __('訂單已付款', 'newebpay-payment');
            exit();
        }

        // 檢查回傳狀態
        if (!in_array($re_Status, array('SUCCESS', 'CUSTOM'))) {
            $msg = '訂單處理失敗: ';
            $order->set_transaction_id('');
            $order->update_status('failed');
            $msg .= urldecode($req_data['Message']);
            $order->add_order_note(__($msg, 'woothemes'));
            echo esc_attr($msg);
            exit();
        }

        // 檢查是否付款
        if (empty($req_data['PayTime'])) {
            $msg = '訂單並未付款';
            $order->set_transaction_id('');
            $order->update_status('failed');
            echo esc_attr($msg);
            exit();
        }

        // 檢查金額
        if ($Amt != $re_Amt) {
            $msg = '金額不一致';
            $order->set_transaction_id('');
            $order->update_status('failed');
            echo esc_attr($msg);
            exit();
        }

        // 訂單備註
        $note_text = '<<<code>藍新金流</code>>>';
        $note_text .= '</br>商店訂單編號：' . $re_MerchantOrderNo;
        $note_text .= '</br>藍新金流支付方式：' . $this->get_payment_type_str($req_data['PaymentType'], !empty($req_data['P2GPaymentType']));
        $note_text .= '</br>藍新金流交易序號：' . $req_data['TradeNo'];
        $order->add_order_note($note_text);

        // 超商取貨
        if (!empty($req_data['CVSCOMName']) || !empty($req_data['StoreName']) || !empty($req_data['StoreAddr'])) {
            $storeName = urldecode($req_data['StoreName']);
            $storeAddr = urldecode($req_data['StoreAddr']);
            $name = urldecode($req_data['CVSCOMName']);
            $phone = $req_data['CVSCOMPhone'];
            $order->update_meta_data('_newebpayStoreName', $storeName);
            $order->update_meta_data('_newebpayStoreAddr', $storeAddr);
            $order->update_meta_data('_newebpayConsignee', $name);
            $order->update_meta_data('_newebpayConsigneePhone', $phone);
            $order->save();
        }

        // 修改訂單狀態
        $order->update_status('processing');

        // 幕後回調時記錄需要清空購物車的訂單
        $this->cart_manager->set_backend_callback_clear_flag($order->get_id());

        $msg = '訂單修改成功';
        $eiChk = $this->gateway->eiChk;
        if ($eiChk == 'yes') {
            $this->gateway->inv->electronic_invoice($order, $re_TradeNo);
        }

        if (sanitize_text_field($_GET['callback']) != '') {
            echo esc_attr($msg);
            exit();
        }
    }

    /**
     * 取得付款類型字串
     * 
     * @param string $payment_type 付款類型
     * @param bool $isEZP 是否為 EZP
     * @return string 付款類型字串
     */
    private function get_payment_type_str($payment_type = '', $isEZP = false)
    {
        $PaymentType_Ary = array(
            'CREDIT'  => '信用卡',
            'WEBATM'  => 'WebATM',
            'VACC'    => 'ATM轉帳',
            'CVS'     => '超商代碼繳費',
            'BARCODE' => '超商條碼繳費',
            'CVSCOM'  => '超商取貨付款',
            'P2GEACC' => '電子帳戶',
            'ACCLINK' => '約定連結存款帳戶',
        );
        $re_str = (isset($PaymentType_Ary[$payment_type])) ? $PaymentType_Ary[$payment_type] : $payment_type;
        $re_str = (!$isEZP) ? $re_str : $re_str . '(ezPay)';
        return $re_str;
    }

    /**
     * 初始化陣列資料
     * 
     * @param array $arr 原始陣列
     * @param string $indexes 索引字串
     * @return array 初始化後的陣列
     */
    private function init_array_data($arr = array(), $indexes = '')
    {
        $index_array = explode(',', $indexes);
        foreach ($index_array as $val) {
            $init_array[$val] = null;
        }
        if (!empty($arr)) {
            return array_merge($init_array, $arr);
        }
        return $init_array;
    }

    /**
     * 檢查 SHA 值是否有效
     * 
     * @param array $return_data 回傳資料
     * @return bool 是否有效
     */
    private function chkShaIsVaildByReturnData($return_data)
    {
        if (empty($return_data['TradeSha'])) {
            return false;
        }
        if (empty($return_data['TradeInfo'])) {
            return false;
        }
        $local_sha = $this->gateway->encProcess->aes_sha256_str(
            $return_data['TradeInfo'],
            $this->gateway->HashKey,
            $this->gateway->HashIV
        );
        if ($return_data['TradeSha'] != $local_sha) {
            return false;
        }
        return true;
    }
}
