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
    public $SmartPaySourceBankID;

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

    public function __construct()
    {
        $this->id  = 'newebpay';
        $this->icon = apply_filters('woocommerce_newebpay_icon', plugins_url('icon/newebpay.png', dirname(dirname(__FILE__))));

        $this->has_fields         = true;
        $this->method_title       = __('藍新金流', 'woocommerce');
        $this->method_description = __('透過藍新科技整合金流輕鬆付款', 'woocommerce');
        
        // 宣告支援 WooCommerce Blocks
        $this->supports = array(
            'products',
            'refunds',
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
        $this->SmartPaySourceBankID = trim($this->get_option('SmartPaySourceBankID'));
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
        
        // 註冊購物車管理的 hooks
        add_action('woocommerce_payment_complete', array($this, 'on_payment_complete'));
        add_action('woocommerce_order_status_processing', array($this, 'on_order_status_processing'));
        add_action('woocommerce_order_status_failed', array($this, 'on_order_status_failed'));
        
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
        echo '<h1>' . _e('藍新金流 收款模組', 'woocommerce') . '</h1>';;
        echo '<p>' . _e('此模組可以讓您使用藍新金流的收款功能', 'woocommerce') . '</p>';
        echo '<p>' . _e('請先至藍新官網申請會員並且啟用相關支付工具', 'woocommerce') . '</p>';
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
        $merchant_order_no = $order->get_id() . 'T' . time(); // prevent duplicate
        $order->update_meta_data('_newebpayMerchantOrderNo', $merchant_order_no);
        $order->save();
        $post_data = array(
            'MerchantID'      => $this->MerchantID, // 商店代號
            'RespondType'     => 'JSON', // 回傳格式
            'TimeStamp'       => time(), // 時間戳記
            'Version'         => '2.3',
            'MerchantOrderNo' => $merchant_order_no,
            'Amt'             => round($order->get_total()),
            'ItemDesc'        => $this->genetateItemDescByOrderItem($order),
            'ExpireDate'      => date('Ymd', time() + intval($this->ExpireDate) * 24 * 60 * 60),
            'Email'           => $order->get_billing_email(),
            'LoginType'       => '0',
            'NotifyURL'       => $this->notify_url, // 幕後
            'ReturnURL'       => $this->get_return_url($order), // 幕前(線上)
            'ClientBackURL'   => wc_get_cart_url(), // 返回商店 wc_get_checkout_url
            'CustomerURL'     => $this->get_return_url($order), // 幕前(線下)
            'LangType'        => $this->LangType,
        );

        // 取得用戶選擇的支付方式
        $selected_payment = '';
        
        // 1. 首先檢查當前類別中是否有選擇的支付方式
        if (!empty($this->nwpSelectedPayment)) {
            $selected_payment = $this->nwpSelectedPayment;
        }
        
        // 2. 如果沒有當前選擇，嘗試從訂單 meta 資料取得之前的選擇
        if (empty($selected_payment)) {
            $selected_payment = $order->get_meta('_nwpSelectedPayment');
        }
        
        // 3. 如果還是沒有，嘗試從 $_POST 中直接取得
        if (empty($selected_payment)) {
            $post_methods = array('selectedmethod', 'newebpay_selected_method', 'nwp_selected_payments');
            foreach ($post_methods as $method) {
                if (!empty($_POST[$method])) {
                    $selected_payment = sanitize_text_field($_POST[$method]);
                    // 立即保存到訂單 meta 和類別屬性
                    $this->nwpSelectedPayment = $selected_payment;
                    $order->update_meta_data('_nwpSelectedPayment', $selected_payment);
                    $order->save();
                    break;
                }
            }
        }
        
        // 取得後台設定的啟用支付方式，用於驗證選擇是否有效
        $get_select_payment = $this->get_selected_payment();
        
        // 驗證選擇的支付方式是否在後台設定中啟用
        $is_valid_payment = false;
        if (!empty($selected_payment)) {
            // 建立支付方式對應表（小寫 -> 設定欄位名稱）
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
                'smartpay' => 'SmartPay'  // 注意：SmartPay 保持駝峰式，不是全大寫
            );
            
            $payment_config_key = $payment_config_map[strtolower($selected_payment)] ?? strtoupper($selected_payment);
            $is_valid_payment = isset($get_select_payment[$payment_config_key]) && $get_select_payment[$payment_config_key] == '1';
        }
        
        // 如果選擇的支付方式無效或沒有選擇，使用第一個啟用的支付方式
        if (!$is_valid_payment) {
            $payment_priority = array('Credit', 'AndroidPay', 'SamsungPay', 'LinePay', 'EsunWallet', 'TaiwanPay', 'Webatm', 'Vacc', 'CVS', 'BARCODE');
            
            foreach ($payment_priority as $payment_method) {
                if (isset($get_select_payment[$payment_method]) && $get_select_payment[$payment_method] == '1') {
                    $selected_payment = strtolower($payment_method);
                    break;
                }
            }
        }
        
        // 添加訂單備註記錄使用的支付方式
        if (!empty($selected_payment)) {
            $order->add_order_note("使用支付方式: " . $selected_payment, 0);
        }
        
        // 只設定用戶選擇的支付方式
        if (!empty($selected_payment)) {
            // 智慧ATM2.0 特殊處理（支援多種格式：smartpay, SmartPay）
            if (strtolower($selected_payment) === 'smartpay') {
                $post_data['VACC'] = 1;
                
                // 取得智慧ATM2.0的設定參數
                $source_type = trim($this->get_option('SmartPaySourceType'));
                $source_bank_id = trim($this->get_option('SmartPaySourceBankID'));
                $source_account_no = trim($this->get_option('SmartPaySourceAccountNo'));
                
                // 加入智慧ATM2.0必要參數
                if (!empty($source_type)) {
                    $post_data['SourceType'] = $source_type;
                }
                if (!empty($source_bank_id)) {
                    $post_data['SourceBankID'] = $source_bank_id;
                }
                if (!empty($source_account_no)) {
                    $post_data['SourceAccountNo'] = $source_account_no;
                }
            } else {
                // 其他支付方式的正常處理
                $post_data[strtoupper($selected_payment)] = 1;
            }
        }

        $cvscom_payed = $get_select_payment['CVSCOMPayed'] ?? '';
        
        $cvscom_not_payed = $get_select_payment['CVSCOMNotPayed'] ?? '';
        
        $custom_cvscom_not_payed = $order->get_meta('_CVSCOMNotPayed') ?? '';

        // 取得訂單備註
        $notes = wc_get_order_notes(array(
            'order_id' => $order->get_id(),
            'type' => 'customer'
        ));

        // 超商取貨(CVSCOM: 1:取貨不付款 2:取貨付款)
        if ($custom_cvscom_not_payed == '1' && $cvscom_not_payed == '1') {
            $post_data['CVSCOM'] = '1';
            if (!empty($notes) && isset($notes[0]->comment_content) && $notes[0]->comment_content == 'CVSCOMPayed') {
                $post_data['CVSCOM'] = '2';
            }
        } elseif ($cvscom_payed == '1' && !empty($notes) && isset($notes[0]->comment_content) && $notes[0]->comment_content == 'CVSCOMPayed') {
            $post_data['CVSCOM'] = '2';
        } 

        $aes    = $this->encProcess->create_mpg_aes_encrypt($post_data, $this->HashKey, $this->HashIV);
        $sha256 = $this->encProcess->aes_sha256_str($aes, $this->HashKey, $this->HashIV);

        return array(
            'MerchantID'  => $this->MerchantID,
            'TradeInfo'   => $aes,
            'TradeSha'    => $sha256,
            'Version'     => '2.3',
            'CartVersion' => 'New_WooCommerce_1_0_1',
        );
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
        $req_data = array();

        // prevent other maker's payment method to show this text
        if (!isset($_REQUEST['TradeSha'])) {
            return;
        }

        if (!empty(sanitize_text_field($_REQUEST['TradeSha']))) {
            if (!$this->chkShaIsVaildByReturnData($_REQUEST)) {
                echo '請重新填單';
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
            // 註：transaction_id 只在付款成功時設定，避免 WooCommerce 誤判需要重新付款
        }

        if (empty($order)) {
            return '交易失敗，請重新填單';
            exit();
        }

        // 統一處理所有非成功狀態
        if (!empty($req_data['Status']) && $req_data['Status'] != 'SUCCESS') {
            // 交易失敗，清除 transaction_id 以確保可以重試付款
            if (isset($order) && $order) {
                $order->set_transaction_id('');
                $order->update_status('failed', sprintf(
                    __('Payment failed via Newebpay. Status: %s, Message: %s', 'newebpay-payment'),
                    esc_attr($req_data['Status']),
                    esc_attr(urldecode($req_data['Message']))
                ));
            }
            $result = ''; // 空的結果，後面會添加重試區塊
        } elseif (empty($req_data['PaymentType']) || empty($req_data['Status'])) {
            // 如果訂單狀態是 failed，不顯示錯誤訊息，後面會有重試區塊
            if (isset($order) && $order && $order->get_status() === 'failed') {
                $result = ''; // 空的結果，後面會添加重試區塊
            } else {
                return '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
            }
        } else {
            $result = '付款方式：' . esc_attr($this->get_payment_type_str($req_data['PaymentType'], !empty($req_data['P2GPaymentType']))) . '<br>';
        }
        switch ($req_data['PaymentType']) {
            case 'CREDIT':
            case 'WEBATM':
            case 'P2GEACC':
            case 'ACCLINK':
                if ($req_data['Status'] == 'SUCCESS') {
                    $result .= '交易成功<br>';
                } else {
                    // 失敗時不顯示錯誤訊息，後面會有統一的重試區塊
                    // $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
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
                    // 設定訂單狀態為失敗，以便顯示重試付款選項
                    $order->update_status('failed', sprintf(
                        __('Payment failed via Newebpay. Error: %s', 'newebpay-payment'),
                        esc_attr(urldecode($req_data['Message']))
                    ));
                    // 失敗時不顯示錯誤訊息，後面會有統一的重試區塊
                    // $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'CVS':
                if (!empty($req_data['CodeNo'])) {
                    $result .= '取號成功<br>';
                    $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
                } else {
                    // 清除 transaction_id 以確保可以重試付款
                    $order->set_transaction_id('');
                    // 設定訂單狀態為失敗，以便顯示重試付款選項
                    $order->update_status('failed', sprintf(
                        __('Payment failed via Newebpay. Error: %s', 'newebpay-payment'),
                        esc_attr(urldecode($req_data['Message']))
                    ));
                    // 失敗時不顯示錯誤訊息，後面會有統一的重試區塊
                    // $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'BARCODE':
                if (!empty($req_data['Barcode_1']) || !empty($req_data['Barcode_2']) || !empty($req_data['Barcode_3'])) {
                    $result .= '取號成功<br>';
                    $result .= '請前往信箱列印繳費單<br>';
                } else {
                    // 清除 transaction_id 以確保可以重試付款
                    $order->set_transaction_id('');
                    // 設定訂單狀態為失敗，以便顯示重試付款選項
                    $order->update_status('failed', sprintf(
                        __('Payment failed via Newebpay. Error: %s', 'newebpay-payment'),
                        esc_attr(urldecode($req_data['Message']))
                    ));
                    // 失敗時不顯示錯誤訊息，後面會有統一的重試區塊
                    // $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
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

        if ($req_data['Status'] != 'SUCCESS') {
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
        } else {
            // 付款成功處理
            $previous_status = $order->get_status();
            
            // 設定交易編號 - 只在付款成功時設定
            $order->set_transaction_id($req_data['TradeNo']);
            $order->update_status('processing');
            
            // 如果訂單之前是失敗狀態，添加備註記錄恢復
            if ($previous_status === 'failed') {
                $order->add_order_note(__('Payment succeeded after previous failure - order recovered', 'newebpay-payment'));
            }
        }
        
        // 自動登入處理 - 無論付款成功或失敗都執行，讓用戶能管理訂單和購物車
        if (!is_user_logged_in()) {
            $user_email = $order->get_billing_email();
            if (!empty($user_email)) {
                $user = get_user_by('email', $user_email);
                if ($user && !is_wp_error($user)) {
                    wp_set_current_user($user->ID);
                    wp_set_auth_cookie($user->ID, true);
                    
                    // 添加訂單備註記錄自動登入
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
                // 如果訂單是訪客訂單但現在有登入用戶，將訂單關聯到該用戶
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
        
        // 如果訂單狀態是 failed 且沒有 transaction_id，添加重試付款按鈕
        if (isset($order) && $order && 
            $order->get_status() === 'failed' && 
            empty($order->get_transaction_id())) {
            
            $checkout_payment_url = $order->get_checkout_payment_url();
            if ($checkout_payment_url) {
                $result .= '<div class="woocommerce-order-retry-payment" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #e74c3c; border-radius: 5px; text-align: center;">';
                $result .= '<h4 style="color: #e74c3c; margin: 0 0 15px 0; font-size: 18px;">💳 付款失敗</h4>';
                $result .= '<p style="margin: 0 0 20px 0; color: #666;">您的付款沒有成功完成，請重新嘗試付款。</p>';
                $result .= '<a href="' . esc_url($checkout_payment_url) . '" class="button alt wc-retry-payment" style="background-color: #e74c3c; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; display: inline-block; font-weight: bold; border: none; cursor: pointer; transition: all 0.3s ease;">🔄 再試一次付款</a>';
                $result .= '</div>';
            }
        }
        
        // 如果訂單已完成付款，隱藏所有重試相關的按鈕和連結
        if (isset($order) && $order && 
            !empty($order->get_transaction_id()) && 
            in_array($order->get_status(), array('processing', 'completed'))) {
            
            // 使用 WordPress 標準方式添加 JavaScript
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

        return $result;
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
        $req_data = array();

        // 檢查SHA值是否正確 MPG1.4版
        if (!empty(sanitize_text_field($_REQUEST['TradeSha']))) {
            if (!$this->chkShaIsVaildByReturnData($_REQUEST)) {
                echo 'SHA vaild fail';
                exit; // 一定要有離開，才會被正常執行
            }
            $req_data = $this->encProcess->create_aes_decrypt(
                sanitize_text_field($_REQUEST['TradeInfo']),
                $this->HashKey,
                $this->HashIV
            );
            if (!is_array($req_data)) {
                echo '解密失敗';
                exit; // 一定要有離開，才會被正常執行
            }
        }

        // 初始化$req_data 避免因index不存在導致NOTICE 若無傳入值的index將設為null
        $init_indexes       = 'Status,Message,TradeNo,MerchantOrderNo,PaymentType,P2GPaymentType,PayTime';
        $req_data           = $this->init_array_data($req_data, $init_indexes);
        $re_MerchantOrderNo = trim($req_data['MerchantOrderNo']);
        $re_Status  = sanitize_text_field($_REQUEST['Status']) != '' ? sanitize_text_field($_REQUEST['Status']) : null;
        $re_TradeNo = $req_data['TradeNo'];
        $re_Amt     = $req_data['Amt'];

        $order = wc_get_order(explode("T", $re_MerchantOrderNo)[0]);
        if (!$order) {
            echo '取得訂單失敗，訂單編號' . esc_attr($re_MerchantOrderNo);
            exit();
        }

        $Amt = round($order->get_total());
        if ($order->is_paid()) {
            echo '訂單已付款';
            exit(); // 已付款便不重複執行
        }

        // 檢查回傳狀態是否為成功
        if (!in_array($re_Status, array('SUCCESS', 'CUSTOM'))) {
            $msg = '訂單處理失敗: ';
            // 清除 transaction_id 以確保可以重試付款
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
            // 清除 transaction_id 以確保可以重試付款
            $order->set_transaction_id('');
            $order->update_status('failed');
            echo esc_attr($msg);
            exit; // 一定要有離開，才會被正常執行
        };

        // 檢查金額是否一樣
        if ($Amt != $re_Amt) {
            $msg = '金額不一致';
            // 清除 transaction_id 以確保可以重試付款
            $order->set_transaction_id('');
            $order->update_status('failed');
            echo esc_attr($msg);
            exit();
        }

        // 訂單備註
        $note_text  = '<<<code>藍新金流</code>>>';
        $note_text .= '</br>商店訂單編號：' . $re_MerchantOrderNo;
        $note_text .= '</br>藍新金流支付方式：' . $this->get_payment_type_str($req_data['PaymentType'], !empty($req_data['P2GPaymentType']));
        $note_text .= '</br>藍新金流交易序號：' . $req_data['TradeNo'];
        $order->add_order_note($note_text);

        // 超商取貨
        if (!empty($req_data['CVSCOMName']) || !empty($req_data['StoreName']) || !empty($req_data['StoreAddr'])) {
            $storeName = urldecode($req_data['StoreName']); // 店家名稱
            $storeAddr = urldecode($req_data['StoreAddr']); // 店家地址
            $name      = urldecode($req_data['CVSCOMName']); // 取貨人姓名
            $phone     = $req_data['CVSCOMPhone'];
            $order->update_meta_data('_newebpayStoreName', $storeName);
            $order->update_meta_data('_newebpayStoreAddr', $storeAddr);
            $order->update_meta_data('_newebpayConsignee', $name);
            $order->update_meta_data('_newebpayConsigneePhone', $phone);
            $order->save();
        }

        // 全部確認過後，修改訂單狀態(處理中，並寄通知信)
        $order->update_status('processing');
        
        $msg   = '訂單修改成功';
        $eiChk = $this->eiChk;
        if ($eiChk == 'yes') {
            $this->inv->electronic_invoice($order, $re_TradeNo);
        }

        if (sanitize_text_field($_GET['callback']) != '') {
            echo esc_attr($msg);
            exit; // 一定要有離開，才會被正常執行
        }
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
        // 支援傳統表單和 WooCommerce Blocks 的參數名稱
        $cvscom_not_payed = sanitize_text_field($_POST['cvscom_not_payed'] ?? '');
        
        // 檢查 WooCommerce Blocks 傳來的 cvscom 資料
        if (empty($cvscom_not_payed) && isset($_POST['cvscom_not_payed_blocks'])) {
            $cvscom_not_payed = sanitize_text_field($_POST['cvscom_not_payed_blocks']);
        }
        
        if ($cvscom_not_payed == 'CVSCOMNotPayed' || $cvscom_not_payed == 'true' || $cvscom_not_payed == '1') {
            $this->nwpCVSCOMNotPayed = 1;
        } else {
            $this->nwpCVSCOMNotPayed = 0;
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
        if (isset($_POST['payment_method']) && $_POST['payment_method'] == $this->id) {
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
            $_POST['payment_method'] = $this->id;
        }
        
        if ($is_newebpay_payment) {
            $this->nwpSelectedPayment = $choose_payment;
            
            return true;
        } else {
            return false;
        }
    }

    /**
     * Output for the order received page.
     *
     * @access public
     * @return void
     */
    public function receipt_page($order)
    {
        echo '<p>' . __('10秒後會自動跳轉到藍新金流支付頁面，或者按下方按鈕直接前往<br>', 'newebpay') . '</p>';
        echo $this->generate_newebpay_form(esc_attr($order));
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
        global $woocommerce;
        $order = wc_get_order($order_id);
        if ($this->nwpCVSCOMNotPayed == 1) {
            $order->update_meta_data('_CVSCOMNotPayed', 1);
        }else{
            $order->update_meta_data('_CVSCOMNotPayed', 0);
        }
        // 儲存選擇的支付方式到訂單 meta 資料
        $order->update_meta_data('_nwpSelectedPayment', $this->nwpSelectedPayment);
        $order->save();
        $order->add_order_note($this->nwpSelectedPayment, 1);
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
    }

    /**
     * Payment form on checkout page
     *
     * @access public
     * @return void
     */
    public function payment_fields()
    {
        echo $this->get_show_data();
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
            $szHtml .= '<label for="CVSCOMNotPayed">超商取貨不付款</label>';
        }
        return $szHtml;
    }

    /**
     * wcBlocksRegistry
     */
    public function wcBlocksRegistry($registry)
    {
        $registry->registerBlockType(
            'newebpay/payment',
            array(
                'render_callback' => array($this, 'wcBlocksRenderCallback'),
            )
        );
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
        $order_id = $order->get_id();

        $payment_method = $order->get_payment_method();

        if ($payment_method == 'newebpay') {
            echo '<button id="checkOrder" data-value="' . $order_id . '">至藍新更新交易狀態</button>';

            if ($this->eiChk == 'yes') {
                echo '<br><br><button id="createInvoice" data-value="' . $order_id . '">開立藍新發票</button>';
            }

            // 引用js
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
     * 處理付款完成事件，確保購物車被清空
     */
    /**
     * 控制重試付款按鈕的顯示
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
     */
    public function filter_order_needs_payment($needs_payment, $order, $valid_statuses)
    {
        // 只處理藍新金流的訂單
        if ($order->get_payment_method() !== 'newebpay') {
            return $needs_payment;
        }
        
        // 只有在訂單已經成功付款的情況下才阻止重新付款
        // 檢查訂單是否已經成功完成或正在處理中，且有 transaction_id
        if (!empty($order->get_transaction_id()) && 
            in_array($order->get_status(), array('processing', 'completed'))) {
            return false;
        }
        
        return $needs_payment;
    }
    
    /**
     * 控制付款完成的有效狀態
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
     * 當付款完成時的處理
     */
    public function on_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        
        // 只處理藍新金流的訂單且訂單狀態為已完成/處理中
        if ($order && 
            $order->get_payment_method() === 'newebpay' && 
            in_array($order->get_status(), array('processing', 'completed')) &&
            $order->is_paid()) {
            
            // 清空購物車 - 只有在購物車不為空時才清空
            if (WC()->cart && !WC()->cart->is_empty()) {
                WC()->cart->empty_cart();
                
                // 添加日誌以便調試
                if (function_exists('wc_get_logger')) {
                    $logger = wc_get_logger();
                    $logger->info('Cart emptied after successful payment', array('source' => 'newebpay-payment', 'order_id' => $order_id));
                }
            }
        }
    }
    
    /**
     * 當訂單狀態變為 processing 時的處理
     */
    public function on_order_status_processing($order_id)
    {
        $order = wc_get_order($order_id);
        
        // 只處理藍新金流的訂單
        if ($order && $order->get_payment_method() === 'newebpay') {
            // 確保購物車被清空（成功付款的後備處理）
            if (WC()->cart && !WC()->cart->is_empty()) {
                WC()->cart->empty_cart();
                
                // 添加日誌以便調試
                if (function_exists('wc_get_logger')) {
                    $logger = wc_get_logger();
                    $logger->info('Cart emptied when order status changed to processing', array('source' => 'newebpay-payment', 'order_id' => $order_id));
                }
            }
        }
    }
    
    /**
     * 當訂單狀態變為 failed 時的處理
     */
    public function on_order_status_failed($order_id)
    {
        $order = wc_get_order($order_id);
        
        // 只處理藍新金流的訂單
        if ($order && $order->get_payment_method() === 'newebpay') {
            // 失敗時不清空購物車，保留商品讓用戶重試
            // 添加日誌以便調試
            if (function_exists('wc_get_logger')) {
                $logger = wc_get_logger();
                $logger->info('Order failed - cart preserved for retry', array('source' => 'newebpay-payment', 'order_id' => $order_id));
            }
        }
    }

    /**
     * 添加自定義樣式來隱藏 WooCommerce Blocks 的預設重試按鈕
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
