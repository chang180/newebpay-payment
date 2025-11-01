<?php
require_once NEWEB_MAIN_PATH . '/includes/nwp/baseNwpMPG.php';

class WC_newebpay extends baseNwpMPG
{
    /**
     * Language Type
     * @var string
     */
    public $LangType;

    /**
     * Merchant ID
     * @var string
     */
    public $MerchantID;

    /**
     * Hash Key
     * @var string
     */
    public $HashKey;

    /**
     * Hash IV
     * @var string
     */
    public $HashIV;

    /**
     * Expire Date
     * @var int
     */
    public $ExpireDate;

    /**
     * Test Mode
     * @var string
     */
    public $TestMode;

    /**
     * Smart Pay Source Type
     * @var string
     */
    public $SmartPaySourceType;

    /**
     * Smart Pay Source Bank ID
     * @var string
     */
    public $SmartPaySourceBankId;

    /**
     * Smart Pay Source Account No
     * @var string
     */
    public $SmartPaySourceAccountNo;

    /**
     * Gateway URL
     * @var string
     */
    public $gateway;

    /**
     * Invoice Gateway URL
     * @var string
     */
    public $inv_gateway;

    /**
     * Query Trade URL
     * @var string
     */
    public $queryTrade;

    /**
     * Electronic Invoice Check
     * @var string
     */
    public $eiChk;

    /**
     * Invoice Merchant ID
     * @var string
     */
    public $InvMerchantID;

    /**
     * CVS COM Not Payed Setting
     * @var int
     */
    public $nwpCVSCOMNotPayed;

    /**
     * Selected Payment Method
     * @var string
     */
    public $nwpSelectedPayment;

    /**
     * Invoice Hash Key
     * @var string
     */
    public $InvHashKey;

    /**
     * Invoice Hash IV
     * @var string
     */
    public $InvHashIV;

    /**
     * Tax Type
     * @var string
     */
    public $TaxType;

    /**
     * Electronic Invoice Status
     * @var string
     */
    public $eiStatus;

    /**
     * Create Status Time
     * @var string
     */
    public $CreateStatusTime;

    /**
     * Notify URL
     * @var string
     */
    public $notify_url;

    /**
     * Electronic Invoice instance
     * @var nwpElectronicInvoice
     */
    public $inv;

    /**
     * Encryption Process instance
     * @var encProcess
     */
    public $encProcess;

    /**
     * Cart Manager instance
     * @var Newebpay_Cart_Manager
     */
    private $cart_manager;

    /**
     * Payment Handler instance
     * @var Newebpay_Payment_Handler
     */
    private $payment_handler;

    /**
     * Response Handler instance
     * @var Newebpay_Response_Handler
     */
    private $response_handler;

    /**
     * Form Handler instance
     * @var Newebpay_Form_Handler
     */
    private $form_handler;

    /**
     * Order Handler instance
     * @var Newebpay_Order_Handler
     */
    private $order_handler;

    /**
     * Blocks Handler instance
     * @var Newebpay_Blocks_Handler
     */
    private $blocks_handler;

    public function __construct()
    {
        $this->id  = 'newebpay';
        $this->icon = apply_filters('woocommerce_newebpay_icon', plugins_url('icon/newebpay.png', dirname(dirname(__FILE__))));

        $this->has_fields         = true;
        $this->method_title       = __('藍新金流', 'newebpay-payment');
        $this->method_description = __('透過藍新科技整合金流輕鬆付款', 'newebpay-payment');
        
        // 宣告支援 WooCommerce Blocks
        $this->supports = array(
            'products',
            'add_payment_method',
            'subscriptions',
            'subscription_cancellation',
            'subscription_suspension',
            'subscription_reactivation',
            'subscription_amount_changes',
            'subscription_date_changes',
            'subscription_payment_method_change',
            'subscription_payment_method_change_customer',
            'subscription_payment_method_change_admin',
            'multiple_subscriptions',
        );

        // Load the form fields.
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();

        // Define user set variables
        $this->title       = $this->get_option('title');
        $this->LangType    = $this->get_option('LangType');
        $this->description = $this->get_option('description');
        $this->MerchantID  = trim($this->get_option('MerchantID'));
        $this->HashKey     = trim($this->get_option('HashKey'));
        $this->HashIV      = trim($this->get_option('HashIV'));
        $this->ExpireDate  = ($this->get_option('ExpireDate') < 1 || $this->get_option('ExpireDate') > 180) ? 7 : $this->get_option('ExpireDate');
        $this->TestMode    = $this->get_option('TestMode');

        // 智慧ATM2.0 參數
        $this->SmartPaySourceType = trim($this->get_option('SmartPaySourceType'));
        $this->SmartPaySourceBankId = trim($this->get_option('SmartPaySourceBankId'));
        $this->SmartPaySourceAccountNo = trim($this->get_option('SmartPaySourceAccountNo'));

        // Test Mode
        if ($this->TestMode == 'yes') {
            $this->gateway     = 'https://ccore.newebpay.com/MPG/mpg_gateway'; // 測試網址
            $this->inv_gateway = 'https://cinv.ezpay.com.tw/API/invoice_issue';
            $this->queryTrade  = 'https://ccore.newebpay.com/API/QueryTradeInfo';
        } else {
            $this->gateway     = 'https://core.newebpay.com/MPG/mpg_gateway'; // 正式網址
            $this->inv_gateway = 'https://inv.ezpay.com.tw/API/invoice_issue';
            $this->queryTrade  = 'https://core.newebpay.com/API/QueryTradeInfo';
        }

        // 發票
        $this->eiChk            = $this->get_option('eiChk');
        $this->InvMerchantID    = trim($this->get_option('InvMerchantID'));
        $this->InvHashKey       = trim($this->get_option('InvHashKey'));
        $this->InvHashIV        = trim($this->get_option('InvHashIV'));
        $this->TaxType          = $this->get_option('TaxType');
        $this->eiStatus         = $this->get_option('eiStatus');
        $this->CreateStatusTime = $this->get_option('CreateStatusTime');
        $this->notify_url       = add_query_arg('wc-api', 'WC_newebpay', home_url('/')) . '&callback=return';

        $invData   = array(
            'eiChk'            => $this->eiChk,
            'invMerchantID'    => $this->InvMerchantID,
            'invHashKey'       => $this->InvHashKey,
            'invHashIV'        => $this->InvHashIV,
            'taxType'          => $this->TaxType,
            'eiStatus'         => $this->eiStatus,
            'createStatusTime' => $this->CreateStatusTime,
            'testMode'         => $this->TestMode,
        );
        $this->inv = nwpElectronicInvoice::get_instance($invData);

        $this->base_action_init();
        $this->encProcess = encProcess::get_instance();

        // add_filter( 'woocommerce_thankyou_page', array( $this, 'thankyou_page' ) ); // 商店付款完成頁面
        add_filter('woocommerce_thankyou_order_received_text', array($this, 'order_received_text'));

        // 初始化購物車管理器
        $this->cart_manager = Newebpay_Cart_Manager::get_instance();
        
        // 初始化處理器
        $this->payment_handler = new Newebpay_Payment_Handler($this);
        $this->response_handler = new Newebpay_Response_Handler($this);
        $this->form_handler = new Newebpay_Form_Handler($this);
        $this->order_handler = new Newebpay_Order_Handler($this);
        $this->blocks_handler = new Newebpay_Blocks_Handler($this);
        
        // 針對 WooCommerce Blocks 添加樣式來隱藏預設的重試按鈕
        add_action('wp_head', array($this, 'add_custom_failed_order_styles'));
        
        // 控制重試付款按鈕的顯示
        add_filter('woocommerce_my_account_my_orders_actions', array($this, 'filter_retry_payment_actions'), 10, 2);
        add_filter('woocommerce_valid_order_statuses_for_payment_complete', array($this, 'filter_payment_complete_statuses'), 10, 2);
        // 暫時禁用這個 filter，可能影響正常付款流程
        // add_filter('woocommerce_order_needs_payment', array($this, 'filter_order_needs_payment'), 10, 3);
        
        // 只使用一個方式來顯示重試按鈕 - 移除重複的 hooks
        // add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page_actions'), 20);
        // add_filter('woocommerce_thankyou_order_received_text', array($this, 'order_received_text_with_retry'), 20, 2);
        
        // add_filter( 'woocommerce_thankyou_page', array( $this, 'thankyou_page' ) ); // 商店付款完成頁面
        add_filter('woocommerce_thankyou_order_received_text', array($this, 'order_received_text'));
    }

    /**
     * Initialise Gateway Settings Form Fields
     *
     * @access public
     * @return void
     * 後台欄位設置
     */
    function init_form_fields()
    {
        $this->form_fields = include NEWEB_MAIN_PATH . '/includes/nwp/nwpSetting.php';
    }

    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     *
     * @access public
     * @return void
     */
    public function admin_options()
    {
        echo '<h1>' . __('藍新金流 收款模組', 'newebpay-payment') . '</h1>';;
        echo '<p>' . __('此模組可以讓您使用藍新金流的收款功能', 'newebpay-payment') . '</p>';
        echo '<p>' . __('請先至藍新官網申請會員並且啟用相關支付工具', 'newebpay-payment') . '</p>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';

        // 引用js
        wp_enqueue_script(
            'my_custom_script',
            plugins_url('assets/js/admin/newebpaySetting.js', dirname(dirname(__FILE__))),
            array('jquery')
        );
    }

    /**
     * Get newebpay Args for passing to newebpay
     *
     * @access public
     * @param mixed $order
     * @return array
     *
     * MPG參數格式
     */
    private function get_newebpay_args($order)
    {
        return $this->payment_handler->get_payment_args($order);
    }

    /**
     * 依照訂單產生物品名稱
     *
     * @access private
     * @param order $order
     * @version 2.0
     * @return string
     */
    private function genetateItemDescByOrderItem($order)
    {
        if (!isset($order)) {
            return '';
        }
        $item_name = $order->get_items();
        $item_cnt  = 1;
        $itemdesc  = '';
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

    public function order_received_text()
    {
        return $this->response_handler->handle_order_received_text();
    }

    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
    function thankyou_page()
    {
        $req_data = array();
        $result = '';

        if (!empty(sanitize_text_field($_REQUEST['TradeSha']))) {
            if (!$this->chkShaIsVaildByReturnData($_REQUEST)) {
                return '請重新填單';
                exit();
            }
            $req_data = $this->encProcess->create_aes_decrypt(
                sanitize_text_field($_REQUEST['TradeInfo']),
                $this->HashKey,
                $this->HashIV
            );
        }

        // 初始化$req_data 避免因index不存在導致NOTICE 若無傳入值的index將設為null
        $init_indexes = 'Status,Message,TradeNo,MerchantOrderNo,PaymentType,P2GPaymentType,BankCode,CodeNo,Barcode_1,Barcode_2,Barcode_3,ExpireDate,CVSCOMName,StoreName,StoreAddr,CVSCOMPhone';
        $req_data     = $this->init_array_data($req_data, $init_indexes);

        if (!empty($req_data['MerchantOrderNo']) && sanitize_text_field($_GET['key']) != '' && preg_match('/^wc_order_/', sanitize_text_field($_GET['key']))) {
            $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_GET['key']));
            $order    = wc_get_order($order_id);   // 原$_REQUEST['order-received']
        }

        if (empty($order)) {
            return '交易失敗，請重新填單';
            exit();
        }

        if (empty($req_data['PaymentType']) || empty($req_data['Status'])) {
            return '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
            exit();
        }

        $result .= '付款方式：' . esc_attr($this->get_payment_type_str($req_data['PaymentType'], !empty($req_data['P2GPaymentType']))) . '<br>';
        switch ($req_data['PaymentType']) {
            case 'CREDIT':
            case 'WEBATM':
            case 'P2GEACC':
            case 'ACCLINK':
                if ($req_data['Status'] == 'SUCCESS') {
                    $previous_status = $order->get_status();
                    
                    // 設定交易編號 - 只在付款成功時設定
                    $order->set_transaction_id($req_data['TradeNo']);
                    $order->update_status('processing');

                    // 如果訂單之前是失敗狀態，添加備註記錄恢復
                    if ($previous_status === 'failed') {
                        $order->add_order_note(__('Payment succeeded after previous failure - order recovered', 'newebpay-payment'));
                    }

                    // 付款成功時立即清空購物車
                    if ($this->cart_manager) {
                        $this->cart_manager->clear_cart_for_order($order->get_id());
                    }

                    $result .= '交易成功<br>';
                } else {
                    // 付款失敗，清除 transaction_id 並設定訂單狀態為 failed 以便顯示重試選項
                    $order->set_transaction_id('');
                    $order->update_status('failed', sprintf(
                        __('Payment failed via Newebpay. Status: %s, Message: %s', 'newebpay-payment'),
                        $req_data['Status'],
                        urldecode($req_data['Message'])
                    ));
                    
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'VACC':
                if (!empty($req_data['BankCode']) && !empty($req_data['CodeNo'])) {
                    $result .= '取號成功<br>';
                    $result .= '銀行代碼：' . esc_attr($req_data['BankCode']) . '<br>';
                    $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
                } else {
                    // 清除 transaction_id 以確保可以重試付款
                    $order->set_transaction_id('');
                    // 取號失敗，設定訂單狀態為 failed
                    $order->update_status('failed', sprintf(
                        __('Virtual account creation failed via Newebpay. Status: %s, Message: %s', 'newebpay-payment'),
                        $req_data['Status'],
                        urldecode($req_data['Message'])
                    ));
                    
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'CVS':
                if (!empty($req_data['CodeNo'])) {
                    $result .= '取號成功<br>';
                    $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
                } else {
                    // 清除 transaction_id 以確保可以重試付款
                    $order->set_transaction_id('');
                    $order->update_status('failed', sprintf(
                        __('CVS payment code creation failed via Newebpay. Status: %s, Message: %s', 'newebpay-payment'),
                        $req_data['Status'],
                        urldecode($req_data['Message'])
                    ));
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'BARCODE':
                if (!empty($req_data['Barcode_1']) || !empty($req_data['Barcode_2']) || !empty($req_data['Barcode_3'])) {
                    $result .= '取號成功<br>';
                    $result .= '請前往信箱列印繳費單<br>';
                } else {
                    // 清除 transaction_id 以確保可以重試付款
                    $order->set_transaction_id('');
                    $order->update_status('failed', sprintf(
                        __('Barcode creation failed via Newebpay. Status: %s, Message: %s', 'newebpay-payment'),
                        $req_data['Status'],
                        urldecode($req_data['Message'])
                    ));
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'CVSCOM':
                if (empty($req_data['CVSCOMName']) || empty($req_data['StoreName']) || empty($req_data['StoreAddr'])) {
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            default:
                // 未來新增之付款方式
                if ($req_data['Status'] == 'SUCCESS') {
                    $result .= '付款方式：' . esc_attr($req_data['PaymentType']) . '<br>';
                    if (!empty($req_data['ExpireDate'])) {
                        $result .= '非即時付款，詳細付款資訊請從信箱確認<br>';    // 非即時付款 部分非即時付款可能沒ExpireDate
                    } else {
                        $result .= '交易成功<br>';    // 即時付款
                    }
                    $result .= '藍新金流交易序號：' . esc_attr($req_data['TradeNo']) . '<br>';
                    break;
                }
                break;
        }
        if (!empty($req_data['CVSCOMName']) || !empty($req_data['StoreName']) || !empty($req_data['StoreAddr'])) {
            $order_id  = (isset($order_id)) ? $order_id : $req_data['MerchantOrderNo'];
            $storeName = urldecode($req_data['StoreName']); // 店家名稱
            $storeAddr = urldecode($req_data['StoreAddr']); // 店家地址
            $name      = urldecode($req_data['CVSCOMName']); // 取貨人姓名
            $phone     = $req_data['CVSCOMPhone'];
            $result .= '<br>取貨人：' . esc_attr($name) . '<br>電話：' . esc_attr($phone) . '<br>店家：' . esc_attr($storeName) . '<br>地址：' . esc_attr($storeAddr) . '<br>';
            $result .= '請等待超商通知取貨<br>';
            if (empty($order->get_meta('_newebpayStoreName'))) {
                $order->update_meta_data('_newebpayStoreName', $storeName);
                $order->update_meta_data('_newebpayStoreAddr', $storeAddr);
                $order->update_meta_data('_newebpayConsignee', $name);
                $order->update_meta_data('_newebpayConsigneePhone', $phone);
                $order->save();
            }
        }

        return $result;
    }

    /**
     * //ready to deprecate
     * convert payment_type
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
        $re_str          = (isset($PaymentType_Ary[$payment_type])) ? $PaymentType_Ary[$payment_type] : $payment_type;
        $re_str          = (!$isEZP) ? $re_str : $re_str . '(ezPay)'; // 智付雙寶
        return $re_str;
    }

    /**
     * check sha value
     */
    private function chkShaIsVaildByReturnData($return_data)
    {
        if (empty($return_data['TradeSha'])) {
            return false;
        }
        if (empty($return_data['TradeInfo'])) {
            return false;
        }
        $local_sha = $this->encProcess->aes_sha256_str(
            $return_data['TradeInfo'],
            $this->HashKey,
            $this->HashIV
        );
        if ($return_data['TradeSha'] != $local_sha) {
            return false;
        }
        return true;
    }

    /**
     * 接收回傳參數驗證
     *
     * @access public
     * @return void
     */
    public function receive_response()
    {
        $this->response_handler->handle_callback_response();
    }

    /**
     * Generate the newebpay button link (POST method)
     *
     * @access public
     * @param mixed $order_id
     * @return string
     */
    public function generate_newebpay_form($order_id)
    {
        $order               = wc_get_order($order_id);
        $newebpay_args       = $this->get_newebpay_args($order);
        $newebpay_gateway    = $this->gateway;
        $newebpay_args_array = array();
        foreach ($newebpay_args as $key => $value) {
            $newebpay_args_array[] = '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
        }

        return '<form id="newebpay" name="newebpay" action="' . $newebpay_gateway . '" method="post" target="_top">' . implode('', $newebpay_args_array) . '
            <input type="submit" class="button-alt" id="submit_newebpay_payment_form" value="' . __('前往 藍新金流 支付頁面', 'newebpay') . '" />
            </form>' . "<script>setTimeout(\"document.forms['newebpay'].submit();\",\"10000\")</script>";
    }

    /**
     * Check the payment method and the chosen payment
     */
    public function validate_fields()
    {
        return $this->form_handler->validate_fields();
    }

    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
    public function receipt_page($order)
    {
        $this->form_handler->display_receipt_page($order);
    }

    /**
     * Process the payment and return the result
     *
     * @access public
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        return $this->payment_handler->process_payment($order_id);
    }

    /**
     * Payment form on checkout page
     *
     * @access public
     * @return void
     */
    public function payment_fields()
    {
        $this->form_handler->display_payment_fields();
    }

    /**
     * show selected payment method on checkout page
     */
    private function get_show_data()
    {
        $payment_method = $this->get_selected_payment();
        $cvscom_not_payed = '';
        $szHtml         = '';
        $szHtml        .= '付款方式 : ';
        $szHtml        .= '<select name="nwp_selected_payments">';

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

        foreach ($payment_method as $payment_method => $value) {
            if ($payment_method == 'CVSCOMNotPayed') {
                $cvscom_not_payed = 1;
                continue;
            }
            
            // 只在測試模式下限制微信和支付寶（如果有技術限制）
            if ($this->settings['TestMode'] == 'yes' && in_array($payment_method, array('EZPWECHAT', 'EZPALIPAY'))) {
                continue;
            }

            $szHtml .= '<option value="' . esc_attr($payment_method) . '">';
            $szHtml .= esc_html($this->convert_payment($payment_method));
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
     * wcBlocksRegistry
     */
    public function wcBlocksRegistry($registry)
    {
        // 使用新的 blocks handler 來註冊 blocks
        $this->blocks_handler->register_blocks();
    }

    /**
     * convert payment type
     */
    private function convert_payment($payment_method)
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


        return $method[$payment_method];
    }

    /**
     *  get comsumer selected payment method
     */
    private function get_selected_payment()
    {
        $payment_method = array();

        // Debug: 記錄所有設定（僅在完整 Debug 模式下）
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( 'Newebpay Settings for payment selection:' );
            foreach ( $this->settings as $key => $value ) {
                if ( strpos( $key, 'Method' ) !== false || in_array( $key, array( 'CREDIT', 'WEBATM', 'VACC', 'CVS', 'BARCODE' ) ) ) {
                    error_log( "  {$key} = {$value}" );
                }
            }
        }

        foreach ($this->settings as $row => $value) {
            if (str_contains($row, 'NwpPaymentMethod') && $value == 'yes') {
                $payment_method[str_replace('NwpPaymentMethod', '', $row)] = 1;
            }
        }

        // Debug: 記錄找到的付款方式（僅在 Debug 模式下）
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( 'Newebpay: Found payment methods: ' . print_r( $payment_method, true ) );
        }

        return $payment_method;
    }

    // 初始化陣列 避免部分交易回傳值差異導致PHP NOTICE
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
     * admin field, show check transaction status button on admin's order page
     */
    public function admin_other_field($order)
    {
        $this->order_handler->display_admin_order_fields($order);
    }
    
    /**
     * 控制重試付款按鈕的顯示
     */
    public function filter_retry_payment_actions($actions, $order)
    {
        return $this->form_handler->filter_retry_payment_actions($actions, $order);
    }
    
    /**
     * 控制訂單是否需要付款
     */
    public function filter_order_needs_payment($needs_payment, $order, $valid_statuses)
    {
        return $this->form_handler->filter_order_needs_payment($needs_payment, $order, $valid_statuses);
    }
    
    /**
     * 控制付款完成的有效狀態
     */
    public function filter_payment_complete_statuses($statuses, $order)
    {
        return $this->form_handler->filter_payment_complete_statuses($statuses, $order);
    }
    

    /**
     * 添加自定義樣式來隱藏 WooCommerce Blocks 的預設重試按鈕
     */
    public function add_custom_failed_order_styles()
    {
        $this->form_handler->add_custom_failed_order_styles();
    }
}
