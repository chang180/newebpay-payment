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

        $this->has_fields         = false;
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
        
        // 添加付款完成時清空購物車的 hook
        add_action('woocommerce_payment_complete', array($this, 'on_payment_complete'), 10, 1);
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

        // 從訂單 meta 資料取得選擇的支付方式
        $selected_payment = $order->get_meta('_nwpSelectedPayment');
        if (!empty($selected_payment)) {
            // 智慧ATM2.0 特殊處理 - 使用 VACC 參數加上額外參數
            if ($selected_payment === 'SmartPay') {
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

        $get_select_payment = $this->get_selected_payment();

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
                $itemdesc .= $item_value->get_name() . ' × ' . $item_value->get_quantity() . '，';
            } elseif ($item_cnt == count($item_name)) {
                $itemdesc .= $item_value->get_name() . ' × ' . $item_value->get_quantity();
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
            $order->set_transaction_id($req_data['TradeNo']);
            $order->save();
        }

        if (empty($order)) {
            return '交易失敗，請重新填單';
            exit();
        }

        if (empty($req_data['PaymentType']) || empty($req_data['Status'])) {
            return '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
            exit();
        }

        $result = '付款方式：' . esc_attr($this->get_payment_type_str($req_data['PaymentType'], !empty($req_data['P2GPaymentType']))) . '<br>';
        switch ($req_data['PaymentType']) {
            case 'CREDIT':
            case 'WEBATM':
            case 'P2GEACC':
            case 'ACCLINK':
                if ($req_data['Status'] == 'SUCCESS') {
                    $result .= '交易成功<br>';
                } else {
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'VACC':
                if (!empty($req_data['BankCode']) && !empty($req_data['CodeNo'])) {

                    $result .= '取號成功<br>';
                    $result .= '銀行代碼：' . esc_attr($req_data['BankCode']) . '<br>';
                    $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
                } else {
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'CVS':
                if (!empty($req_data['CodeNo'])) {
                    $result .= '取號成功<br>';
                    $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
                } else {
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'BARCODE':
                if (!empty($req_data['Barcode_1']) || !empty($req_data['Barcode_2']) || !empty($req_data['Barcode_3'])) {
                    $result .= '取號成功<br>';
                    $result .= '請前往信箱列印繳費單<br>';
                } else {
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

        if ($req_data['Status'] != 'SUCCESS') {
            if ($order->is_paid()) {
                $order->add_order_note(__('Payment failed within paid order', 'newebpay-payment'));
                $order->save();
            } else {
                $order->update_status('failed', sprintf(
                    __('Payment failed: %1$s (%2$s)', 'newebpay-payment'),
                    $req_data['Status'],
                    $req_data['Message']
                ));
            }
        } else {
            // 付款成功處理
            $order->update_status('processing');
            
            // 清空購物車 - 只有在付款成功且購物車不為空時才清空
            if (WC()->cart && !WC()->cart->is_empty()) {
                WC()->cart->empty_cart();
            }
            
            // 如果是訪客結帳，嘗試自動登入 (如果用戶帳號存在)
            if (!is_user_logged_in()) {
                $user_email = $order->get_billing_email();
                if (!empty($user_email)) {
                    $user = get_user_by('email', $user_email);
                    if ($user && !is_wp_error($user)) {
                        wp_set_current_user($user->ID);
                        wp_set_auth_cookie($user->ID, true);
                        
                        // 添加訂單備註記錄自動登入
                        $order->add_order_note(__('Customer automatically logged in after payment', 'newebpay-payment'));
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
                        $order->add_order_note(__('Order linked to logged-in customer after payment', 'newebpay-payment'));
                    }
                }
            }
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
                    $order->update_status('processing');
                    
                    // 清空購物車 - 只有在付款成功且購物車不為空時才清空
                    if (WC()->cart && !WC()->cart->is_empty()) {
                        WC()->cart->empty_cart();
                    }
                    
                    $result .= '交易成功<br>';
                } else {
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'VACC':
                if (!empty($req_data['BankCode']) && !empty($req_data['CodeNo'])) {
                    $result .= '取號成功<br>';
                    $result .= '銀行代碼：' . esc_attr($req_data['BankCode']) . '<br>';
                    $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
                } else {
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'CVS':
                if (!empty($req_data['CodeNo'])) {
                    $result .= '取號成功<br>';
                    $result .= '繳費代碼：' . esc_attr($req_data['CodeNo']) . '<br>';
                } else {
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'BARCODE':
                if (!empty($req_data['Barcode_1']) || !empty($req_data['Barcode_2']) || !empty($req_data['Barcode_3'])) {
                    $result .= '取號成功<br>';
                    $result .= '請前往信箱列印繳費單<br>';
                } else {
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
            $order->update_status('cancelled');
            $msg .= urldecode($req_data['Message']);
            $order->add_order_note(__($msg, 'woothemes'));
            echo esc_attr($msg);
            exit();
        }

        // 檢查是否付款
        if (empty($req_data['PayTime'])) {
            $msg = '訂單並未付款';
            $order->update_status('cancelled');
            echo esc_attr($msg);
            exit; // 一定要有離開，才會被正常執行
        };

        // 檢查金額是否一樣
        if ($Amt != $re_Amt) {
            $msg = '金額不一致';
            $order->update_status('cancelled');
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
        
        // 清空購物車 - 只有在付款成功且購物車不為空時才清空
        if (WC()->cart && !WC()->cart->is_empty()) {
            WC()->cart->empty_cart();
        }
        
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
        foreach ($payment_method as $payment_method => $value) {
            if ($payment_method == 'CVSCOMNotPayed') {
                $cvscom_not_payed = 1;
                continue;
            }
            // 測試模式暫不開放 WechatPay 和 Alipay
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
    public function on_payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        
        // 只處理藍新金流的訂單
        if ($order && $order->get_payment_method() === 'newebpay') {
            // 清空購物車 - 只有在購物車不為空時才清空
            if (WC()->cart && !WC()->cart->is_empty()) {
                WC()->cart->empty_cart();
            }
        }
    }
}
