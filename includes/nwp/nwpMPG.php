<?php
require_once NEWEB_MAIN_PATH . '/includes/nwp/baseNwpMPG.php';

class WC_newebpay extends baseNwpMPG
{
    public function __construct()
    {
        $this->id  = 'newebpay';
        $this->icon = apply_filters('woocommerce_newebpay_icon', plugins_url('icon/newebpay.png', dirname(dirname(__FILE__))));

        $this->has_fields         = false;
        $this->method_title       = __('藍新金流', 'woocommerce');
        $this->method_description = __('透過藍新科技整合金流輕鬆付款', 'woocommerce');

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

        // 信用卡分期付款期數設定
        $inst_periods_str = trim($this->get_option('NwpPaymentMethodInstPeriods'));
        $this->InstPeriods = array();
        if (!empty($inst_periods_str)) {
            // 解析期數字串（用逗號分隔）
            $periods = explode(',', $inst_periods_str);
            // 有效期數：3, 6, 12, 18, 24, 30
            $valid_periods = array(3, 6, 12, 18, 24, 30);
            foreach ($periods as $period) {
                $period = trim($period);
                $period_int = (int)$period;
                // 驗證期數是否為有效值
                if (in_array($period_int, $valid_periods)) {
                    $this->InstPeriods[] = $period_int;
                }
            }
            // 移除重複值並排序
            $this->InstPeriods = array_unique($this->InstPeriods);
            sort($this->InstPeriods);
        }

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
        // 當訂單已標記為失敗/取消時，不顯示訂單明細/帳單地址等「已收到訂單」畫面內容
        add_action('woocommerce_before_thankyou', array($this, 'maybe_hide_thankyou_order_details'), 1);
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

        // 從訂單 meta 取得：選擇的支付方式、是否勾選「取貨不付款」
        // 邏輯：1) CVSCOM 同一參數 2=取貨付款/1=取貨不付款  2) 取貨付款為獨立支付方式  3) 取貨不付款為搭配參數（checkbox）
        $selected_payment   = $order->get_meta('_nwpSelectedPayment');
        $cvscom_not_payed  = (int) $order->get_meta('_CVSCOMNotPayed');

        if (!empty($selected_payment)) {
            if ($cvscom_not_payed === 1) {
                // 有勾選「取貨不付款」：送 CVSCOM=1，並依選擇的支付方式決定是否一併送出該方式
                $post_data['CVSCOM'] = '1';
                if ($selected_payment !== 'CVSCOMPayed') {
                    // 搭配其他支付方式（信用卡、ATM 等）→ 送該方式 + CVSCOM=1
                    if ($selected_payment === 'SmartPay') {
                        $post_data['VACC'] = 1;
                        $source_type = trim($this->get_option('SmartPaySourceType'));
                        $source_bank_id = trim($this->get_option('SmartPaySourceBankId'));
                        $source_account_no = trim($this->get_option('SmartPaySourceAccountNo'));
                        if (!empty($source_type)) {
                            $post_data['SourceType'] = $source_type;
                        }
                        if (!empty($source_bank_id)) {
                            $post_data['SourceBankId'] = $source_bank_id;
                        }
                        if (!empty($source_account_no)) {
                            $post_data['SourceAccountNo'] = $source_account_no;
                        }
                    } elseif ($selected_payment === 'Inst') {
                        $inst_flag = $order->get_meta('_nwpInstFlag');
                        if (!empty($inst_flag) && in_array((int)$inst_flag, [3, 6, 12, 18, 24, 30])) {
                            $post_data['InstFlag'] = (int)$inst_flag;
                        } else {
                            $post_data['InstFlag'] = 1;
                        }
                    } else {
                        $post_data[strtoupper($selected_payment)] = 1;
                    }
                }
                // 若選的是 CVSCOMPayed + 勾選取貨不付款 → 只送 CVSCOM=1，上面不進入
            } else {
                // 未勾選取貨不付款：依選擇的支付方式送參數
                if ($selected_payment === 'SmartPay') {
                    $post_data['VACC'] = 1;
                    $source_type = trim($this->get_option('SmartPaySourceType'));
                    $source_bank_id = trim($this->get_option('SmartPaySourceBankId'));
                    $source_account_no = trim($this->get_option('SmartPaySourceAccountNo'));
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
                    // 超商取貨付款（獨立支付方式）→ 只送 CVSCOM=2
                    $post_data['CVSCOM'] = '2';
                } elseif ($selected_payment === 'Inst') {
                    $inst_flag = $order->get_meta('_nwpInstFlag');
                    if (!empty($inst_flag) && in_array((int)$inst_flag, [3, 6, 12, 18, 24, 30])) {
                        $post_data['InstFlag'] = (int)$inst_flag;
                    } else {
                        $post_data['InstFlag'] = 1;
                    }
                } else {
                    $post_data[strtoupper($selected_payment)] = 1;
                }
            }
        }

        // 只有在明確選擇了 CVSCOM 相關支付方式時才設定 CVSCOM 參數
        // 避免未選擇時也送出參數導致支付方式多出超商取貨選項
        // CVSCOM 參數應該已經在上面根據 selected_payment 設定完成
        // 如果 selected_payment 不是 CVSCOMPayed 或 CVSCOMNotPayed，就不應該設定 CVSCOM 參數 

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

        // 若已在 POST 階段處理並存入訊息（PRG），則 GET 階段直接顯示該訊息
        if (!isset($_REQUEST['TradeSha'])) {
            // 嘗試從 query 取得訂單
            $order_id_from_query = absint(get_query_var('order-received'));
            if ($order_id_from_query) {
                $order = wc_get_order($order_id_from_query);
                if ($order && method_exists($order, 'get_payment_method') && $order->get_payment_method() === $this->id) {
                    $stored_message = $order->get_meta('_newebpay_return_message');
                    if (!empty($stored_message)) {
                        return $stored_message;
                    }
                }
            }
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
                // ATM 轉帳取號成功時，Status 可能是 SUCCESS 或 CUSTOM
                // 只要 BankCode 和 CodeNo 存在，就表示取號成功
                if (!empty($req_data['BankCode']) && !empty($req_data['CodeNo'])) {
                    $result .= '取號成功<br>';
                    $result .= '銀行代碼：' . esc_attr($req_data['BankCode']) . '<br>';
                    $result .= '繳費帳號：' . esc_attr($req_data['CodeNo']) . '<br>';
                    if (!empty($req_data['ExpireDate'])) {
                        $result .= '繳費期限：' . esc_attr($req_data['ExpireDate']) . '<br>';
                    }
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

        // ATM 轉帳、超商代碼、超商條碼等非即時付款方式，取號成功時 Status 可能是 CUSTOM
        // 需要特別處理這些情況
        $is_vacc_success = ($req_data['PaymentType'] == 'VACC' && !empty($req_data['BankCode']) && !empty($req_data['CodeNo']));
        $is_cvs_success = ($req_data['PaymentType'] == 'CVS' && !empty($req_data['CodeNo']));
        $is_barcode_success = ($req_data['PaymentType'] == 'BARCODE' && (!empty($req_data['Barcode_1']) || !empty($req_data['Barcode_2']) || !empty($req_data['Barcode_3'])));
        
        if ($req_data['Status'] != 'SUCCESS' && !$is_vacc_success && !$is_cvs_success && !$is_barcode_success) {
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
            // 即時付款成功，或非即時付款取號成功，都將訂單設為處理中
            $order->update_status('processing');
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
                    $result .= '交易成功<br>';
                } else {
                    $result .= '交易失敗，請重新填單<br>錯誤代碼：' . esc_attr($req_data['Status']) . '<br>錯誤訊息：' . esc_attr(urldecode($req_data['Message']));
                }
                break;
            case 'VACC':
                // ATM 轉帳取號成功時，Status 可能是 SUCCESS 或 CUSTOM
                // 只要 BankCode 和 CodeNo 存在，就表示取號成功
                if (!empty($req_data['BankCode']) && !empty($req_data['CodeNo'])) {
                    $result .= '取號成功<br>';
                    $result .= '銀行代碼：' . esc_attr($req_data['BankCode']) . '<br>';
                    $result .= '繳費帳號：' . esc_attr($req_data['CodeNo']) . '<br>';
                    if (!empty($req_data['ExpireDate'])) {
                        $result .= '繳費期限：' . esc_attr($req_data['ExpireDate']) . '<br>';
                    }
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
        $choose_payment = sanitize_text_field(wp_unslash($_POST['nwp_selected_payments']));
        if ($_POST['payment_method'] == $this->id) {
            $this->nwpSelectedPayment = $choose_payment;
            // 超商取貨不付款為搭配參數：勾選時只記住「取貨不付款」，支付方式仍為下拉選的值（送參時再依此組 CVSCOM=1 與該方式）
            if (isset($_POST['cvscom_not_payed']) && sanitize_text_field(wp_unslash($_POST['cvscom_not_payed'])) === 'CVSCOMNotPayed') {
                $this->nwpCVSCOMNotPayed = 1;
            } else {
                $this->nwpCVSCOMNotPayed = 0;
            }
            
            // 驗證信用卡分期期數（如果選擇了分期付款）
            if ($choose_payment === 'Inst') {
                $inst_period = isset($_POST['nwp_inst_period']) ? sanitize_text_field($_POST['nwp_inst_period']) : '';
                
                // 如果商店設定了期數，使用者必須選擇一個期數
                if (!empty($this->InstPeriods)) {
                    if (empty($inst_period)) {
                        wc_add_notice(__('請選擇分期期數', 'woocommerce'), 'error');
                        return false;
                    }
                    // 驗證期數是否為有效值（3, 6, 12, 18, 24, 30）
                    $valid_periods = array(3, 6, 12, 18, 24, 30);
                    $period_int = (int)$inst_period;
                    if (!in_array($period_int, $valid_periods)) {
                        wc_add_notice(__('請選擇有效的分期期數', 'woocommerce'), 'error');
                        return false;
                    }
                    // 檢查是否在允許的期數範圍內
                    if (!in_array($period_int, $this->InstPeriods)) {
                        wc_add_notice(__('選擇的分期期數不在允許的範圍內', 'woocommerce'), 'error');
                        return false;
                    }
                    // 儲存選擇的期數到屬性中，供 process_payment() 使用
                    $this->nwpInstFlag = $period_int;
                } else {
                    // 商店沒有設定期數，使用者可以選擇或不選（不選則使用預設值全開）
                    if (!empty($inst_period)) {
                        // 驗證期數是否為有效值（3, 6, 12, 18, 24, 30）
                        $valid_periods = array(3, 6, 12, 18, 24, 30);
                        $period_int = (int)$inst_period;
                        if (!in_array($period_int, $valid_periods)) {
                            wc_add_notice(__('請選擇有效的分期期數', 'woocommerce'), 'error');
                            return false;
                        }
                        $this->nwpInstFlag = $period_int;
                    } else {
                        // 沒有選擇期數，使用預設值（全開）
                        $this->nwpInstFlag = '';
                    }
                }
            }
            
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
        
        // 儲存信用卡分期期數（如果選擇了分期付款）
        if ($this->nwpSelectedPayment === 'Inst' && isset($this->nwpInstFlag)) {
            $order->update_meta_data('_nwpInstFlag', $this->nwpInstFlag);
        }
        
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
        foreach ($payment_method as $method_key => $value) {
            if ($method_key == 'CVSCOMNotPayed') {
                $cvscom_not_payed = 1;
                continue;
            }
            // 測試模式暫不開放 WechatPay 和 Alipay
            if ($this->settings['TestMode'] == 'yes' && in_array($method_key, array('EZPWECHAT', 'EZPALIPAY'))) {
                continue;
            }
            $szHtml .= '<option value="' . esc_attr($method_key) . '">';
            $szHtml .= esc_html($this->convert_payment($method_key));
            $szHtml .= '</option>';
        }
        $szHtml .= '</select>';
        
        // 信用卡分期期數選擇（如果有設定期數）
        $inst_enabled = isset($payment_method['Inst']) && $payment_method['Inst'] == 1;
        if ($inst_enabled && !empty($this->InstPeriods)) {
            $szHtml .= '<br><div id="nwp_inst_period_wrapper" style="display:none;">';
            $szHtml .= '<label for="nwp_inst_period">分期期數：<span class="required">*</span></label>';
            $szHtml .= '<select name="nwp_inst_period" id="nwp_inst_period" required>';
            $first_period = true;
            foreach ($this->InstPeriods as $period) {
                $selected = $first_period ? ' selected' : '';
                $szHtml .= '<option value="' . esc_attr($period) . '"' . $selected . '>' . esc_html($period) . '期</option>';
                $first_period = false;
            }
            $szHtml .= '</select>';
            $szHtml .= '</div>';
            // 添加 JavaScript 來控制顯示/隱藏
            $szHtml .= '<script type="text/javascript">
                (function() {
                    var paymentSelect = document.querySelector("select[name=\'nwp_selected_payments\']");
                    var periodWrapper = document.getElementById("nwp_inst_period_wrapper");
                    if (paymentSelect && periodWrapper) {
                        function togglePeriodSelect() {
                            if (paymentSelect.value === "Inst") {
                                periodWrapper.style.display = "block";
                            } else {
                                periodWrapper.style.display = "none";
                            }
                        }
                        paymentSelect.addEventListener("change", togglePeriodSelect);
                        togglePeriodSelect(); // 初始化檢查
                    }
                })();
            </script>';
        }
        
        if ($cvscom_not_payed == 1) {
            $szHtml .= '<br><span id="nwp_cvscom_not_payed_wrapper"><input type="checkbox" name="cvscom_not_payed" id="CVSCOMNotPayed" value="CVSCOMNotPayed"><label for="CVSCOMNotPayed">超商取貨不付款</label></span>';
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

        foreach ($this->settings as $row => $value) {
            if (str_contains($row, 'NwpPaymentMethod') && $value == 'yes') {
                $payment_method[str_replace('NwpPaymentMethod', '', $row)] = 1;
            }
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
     * thankyou.php 即使在 failed 分支，仍會執行 woocommerce_thankyou hook（預設會輸出訂單明細）。
     * 這裡在藍新金流且訂單失敗/取消時移除該輸出，避免顯示完整訂單資料。
     */
    public function maybe_hide_thankyou_order_details($order_id)
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }
        if (method_exists($order, 'get_payment_method') && $order->get_payment_method() !== $this->id) {
            return;
        }
        if ($order->has_status(array('failed', 'cancelled'))) {
            // 移除所有可能輸出訂單資訊的 hook（保留「再試一次」按鈕，但隱藏詳細明細）
            remove_action('woocommerce_thankyou', 'woocommerce_order_details_table', 10);
            remove_action('woocommerce_thankyou_' . $this->id, array($this, 'receipt_page'), 10);

            // 隱藏訂單明細
            echo '<style type="text/css">
                .woocommerce-order .woocommerce-order-details,
                .woocommerce-order .woocommerce-customer-details,
                .woocommerce-order .woocommerce-table--order-details,
                .woocommerce-order table.woocommerce-table--order-details {
                    display: none !important;
                }
            </style>';
        }
    }

    /**
     * 交易失敗時提供返回連結
     */
    private function get_return_links_html()
    {
        $cart_url = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/');
        $shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/');
        if (empty($shop_url)) {
            $shop_url = home_url('/');
        }
        return '<div class="wc-block-order-confirmation-status__actions">'
            . '<button type="button" onclick="window.location.href=\'' . esc_js($cart_url) . '\'" class="wc-block-order-confirmation-status__button wc-block-order-confirmation-status__button--cart">返回購物車</button>'
            . '<button type="button" onclick="window.location.href=\'' . esc_js($shop_url) . '\'" class="wc-block-order-confirmation-status__button wc-block-order-confirmation-status__button--shop">返回商店</button>'
            . '</div>';
    }

    /**
     * 儲存/輸出用：允許基本排版與按鈕連結
     */
    private function sanitize_return_message($html)
    {
        if (empty($html)) {
            return '';
        }
        return wp_kses($html, array(
            'br' => array(),
            'p' => array('class' => true),
            'div' => array('class' => true),
            'a' => array('href' => true, 'class' => true),
            'button' => array('class' => true, 'type' => true, 'onclick' => true),
        ));
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
        }
    }
}
