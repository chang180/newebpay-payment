<?php
/**
 * 藍新金流訂單處理器
 * 
 * 負責處理訂單狀態相關的邏輯，包括：
 * - 訂單狀態管理
 * - 訂單備註處理
 * - 管理員功能
 * 
 * @package Newebpay_Payment
 * @since 1.0.10
 */

if (!defined('ABSPATH')) {
    exit;
}

class Newebpay_Order_Handler
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
     * 處理訂單狀態更新
     * 
     * @param WC_Order $order 訂單物件
     * @param string $status 新狀態
     * @param string $note 備註
     * @return void
     */
    public function update_order_status($order, $status, $note = '')
    {
        $order->update_status($status, $note);
        
        // 記錄狀態變更日誌
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->info('Order status updated', array(
                'source' => 'newebpay-payment',
                'order_id' => $order->get_id(),
                'old_status' => $order->get_status(),
                'new_status' => $status,
                'note' => $note
            ));
        }
    }

    /**
     * 處理付款成功
     * 
     * @param WC_Order $order 訂單物件
     * @param string $transaction_id 交易編號
     * @param array $payment_data 付款資料
     * @return void
     */
    public function handle_payment_success($order, $transaction_id, $payment_data = array())
    {
        $previous_status = $order->get_status();

        // 設定交易編號
        $order->set_transaction_id($transaction_id);
        $this->update_order_status($order, 'processing', __('Payment completed via Newebpay', 'newebpay-payment'));

        // 如果訂單之前是失敗狀態，添加備註記錄恢復
        if ($previous_status === 'failed') {
            $order->add_order_note(__('Payment succeeded after previous failure - order recovered', 'newebpay-payment'));
        }

        // 添加付款成功備註
        $this->add_payment_success_note($order, $payment_data);
    }

    /**
     * 處理付款失敗
     * 
     * @param WC_Order $order 訂單物件
     * @param string $error_message 錯誤訊息
     * @param string $error_code 錯誤代碼
     * @return void
     */
    public function handle_payment_failure($order, $error_message, $error_code = '')
    {
        // 清除 transaction_id 以確保可以重試付款
        $order->set_transaction_id('');
        
        $note = sprintf(
            __('Payment failed via Newebpay. Error: %s', 'newebpay-payment'),
            $error_message
        );
        
        if (!empty($error_code)) {
            $note .= sprintf(' (Code: %s)', $error_code);
        }
        
        $this->update_order_status($order, 'failed', $note);
        
        // 添加調試日誌
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->error('Payment failed', array(
                'source' => 'newebpay-payment',
                'order_id' => $order->get_id(),
                'error_message' => $error_message,
                'error_code' => $error_code,
                'transaction_id' => $order->get_transaction_id(),
                'status' => $order->get_status()
            ));
        }
    }

    /**
     * 添加付款成功備註
     * 
     * @param WC_Order $order 訂單物件
     * @param array $payment_data 付款資料
     * @return void
     */
    private function add_payment_success_note($order, $payment_data)
    {
        $note_text = '<<<code>藍新金流</code>>>';
        $note_text .= '</br>商店訂單編號：' . $order->get_meta('_newebpayMerchantOrderNo');
        
        if (!empty($payment_data['PaymentType'])) {
            $note_text .= '</br>藍新金流支付方式：' . $this->get_payment_type_str($payment_data['PaymentType'], !empty($payment_data['P2GPaymentType']));
        }
        
        if (!empty($payment_data['TradeNo'])) {
            $note_text .= '</br>藍新金流交易序號：' . $payment_data['TradeNo'];
        }
        
        $order->add_order_note($note_text);
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
     * 處理超商取貨資訊
     * 
     * @param WC_Order $order 訂單物件
     * @param array $cvscom_data 超商取貨資料
     * @return void
     */
    public function handle_cvscom_info($order, $cvscom_data)
    {
        if (empty($cvscom_data['CVSCOMName']) || empty($cvscom_data['StoreName']) || empty($cvscom_data['StoreAddr'])) {
            return;
        }

        $storeName = urldecode($cvscom_data['StoreName']);
        $storeAddr = urldecode($cvscom_data['StoreAddr']);
        $name = urldecode($cvscom_data['CVSCOMName']);
        $phone = $cvscom_data['CVSCOMPhone'] ?? '';

        // 更新訂單 meta 資料
        $order->update_meta_data('_newebpayStoreName', $storeName);
        $order->update_meta_data('_newebpayStoreAddr', $storeAddr);
        $order->update_meta_data('_newebpayConsignee', $name);
        $order->update_meta_data('_newebpayConsigneePhone', $phone);
        $order->save();

        // 添加超商取貨備註
        $note = sprintf(
            __('CVS Pickup Info - Name: %s, Store: %s, Address: %s, Phone: %s', 'newebpay-payment'),
            $name,
            $storeName,
            $storeAddr,
            $phone
        );
        $order->add_order_note($note);
    }

    /**
     * 處理管理員訂單頁面功能
     * 
     * @param WC_Order $order 訂單物件
     * @return void
     */
    public function display_admin_order_fields($order)
    {
        $order_id = $order->get_id();
        $payment_method = $order->get_payment_method();

        if ($payment_method == 'newebpay') {
            echo '<button id="checkOrder" data-value="' . $order_id . '">' . __('至藍新更新交易狀態', 'newebpay-payment') . '</button>';

            if ($this->gateway->eiChk == 'yes') {
                echo '<br><br><button id="createInvoice" data-value="' . $order_id . '">' . __('開立藍新發票', 'newebpay-payment') . '</button>';
            }

            // 引用 JavaScript
            wp_enqueue_script(
                'queryTrade',
                plugins_url('assets/js/admin/newebpayAdminAjax.js', dirname(dirname(__FILE__))),
                array('jquery')
            );
            
            // 傳遞 ajaxurl 給 JavaScript
            wp_localize_script('queryTrade', 'newebpay_ajax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('newebpay_nonce')
            ));
        }
    }

    /**
     * 檢查訂單是否需要付款
     * 
     * @param WC_Order $order 訂單物件
     * @return bool 是否需要付款
     */
    public function order_needs_payment($order)
    {
        // 只處理藍新金流的訂單
        if ($order->get_payment_method() !== 'newebpay') {
            return true;
        }
        
        // 如果訂單已經有 transaction_id 且狀態為 processing 或 completed，則不需要付款
        if (!empty($order->get_transaction_id()) && 
            in_array($order->get_status(), array('processing', 'completed'))) {
            return false;
        }
        
        return true;
    }

    /**
     * 取得付款完成的有效狀態
     * 
     * @param array $statuses 狀態陣列
     * @param WC_Order $order 訂單物件
     * @return array 過濾後的狀態陣列
     */
    public function get_payment_complete_statuses($statuses, $order)
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
     * 處理訂單狀態變更事件
     * 
     * @param int $order_id 訂單 ID
     * @param string $old_status 舊狀態
     * @param string $new_status 新狀態
     * @return void
     */
    public function handle_order_status_change($order_id, $old_status, $new_status)
    {
        $order = wc_get_order($order_id);
        
        if (!$order || $order->get_payment_method() !== 'newebpay') {
            return;
        }

        // 記錄狀態變更
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->info('Order status changed', array(
                'source' => 'newebpay-payment',
                'order_id' => $order_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'transaction_id' => $order->get_transaction_id()
            ));
        }

        // 根據狀態執行特定動作
        switch ($new_status) {
            case 'processing':
                $this->handle_processing_status($order);
                break;
            case 'completed':
                $this->handle_completed_status($order);
                break;
            case 'failed':
                $this->handle_failed_status($order);
                break;
        }
    }

    /**
     * 處理處理中狀態
     * 
     * @param WC_Order $order 訂單物件
     * @return void
     */
    private function handle_processing_status($order)
    {
        // 可以在此處添加處理中狀態的特定邏輯
        // 例如：發送確認郵件、更新庫存等
    }

    /**
     * 處理已完成狀態
     * 
     * @param WC_Order $order 訂單物件
     * @return void
     */
    private function handle_completed_status($order)
    {
        // 可以在此處添加完成狀態的特定邏輯
        // 例如：發送完成郵件、更新統計等
    }

    /**
     * 處理失敗狀態
     * 
     * @param WC_Order $order 訂單物件
     * @return void
     */
    private function handle_failed_status($order)
    {
        // 可以在此處添加失敗狀態的特定邏輯
        // 例如：發送失敗通知、恢復庫存等
    }

    /**
     * 取得訂單摘要資訊
     * 
     * @param WC_Order $order 訂單物件
     * @return array 訂單摘要資訊
     */
    public function get_order_summary($order)
    {
        return array(
            'order_id' => $order->get_id(),
            'status' => $order->get_status(),
            'payment_method' => $order->get_payment_method(),
            'transaction_id' => $order->get_transaction_id(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'date_created' => $order->get_date_created(),
            'customer_email' => $order->get_billing_email(),
            'newebpay_merchant_order_no' => $order->get_meta('_newebpayMerchantOrderNo'),
            'newebpay_selected_payment' => $order->get_meta('_nwpSelectedPayment'),
        );
    }
}
