<?php

/**
 * nwp API
 */
class nwpOthersAPI extends WC_newebpay
{
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
	 * Test Mode
	 * @var string
	 */
	public $TestMode;

	/**
	 * Invoice status
	 * @var string
	 */
	public $inv_status;

	/**
	 * Electronic Invoice instance
	 * @var nwpElectronicInvoice
	 */
	public $inv;

	/**
	 * Query Trade API URL
	 * @var string
	 */
	public $queryTrade;

	/**
	 * Credit Close API URL
	 * @var string
	 */
	public $creditClose;

	/**
	 * Encryption Process instance
	 * @var encProcess
	 */
	public $encProcess;

	/**
	 * construct
	 */
	public function __construct()
	{
		$gateway_settings = get_option('woocommerce_newebpay_settings', '');

		if (!empty($gateway_settings)) {
			$this->MerchantID = $gateway_settings['MerchantID'];
			$this->HashKey    = $gateway_settings['HashKey'];
			$this->HashIV     = $gateway_settings['HashIV'];
			$this->TestMode   = $gateway_settings['TestMode'];

			$invData = array(
				'eiChk'            => $gateway_settings['eiChk'],
				'invMerchantID'    => $gateway_settings['InvMerchantID'],
				'invHashKey'       => $gateway_settings['InvHashKey'],
				'invHashIV'        => $gateway_settings['InvHashIV'],
				'taxType'          => $gateway_settings['TaxType'],
				'eiStatus'         => $gateway_settings['eiStatus'],
				'createStatusTime' => $gateway_settings['CreateStatusTime'],
				'testMode'         => $gateway_settings['TestMode'],
			);

			$this->inv_status = $gateway_settings['eiChk'];

			$this->inv         = nwpElectronicInvoice::get_instance($invData);
			$this->queryTrade  = ($this->TestMode == 'yes') ? 'https://ccore.newebpay.com/API/QueryTradeInfo' : 'https://core.newebpay.com/API/QueryTradeInfo';
			$this->creditClose = ($this->TestMode == 'yes') ? 'https://ccore.newebpay.com/API/CreditCard/Close' : 'https://core.newebpay.com/API/CreditCard/Close';
		}

		$this->encProcess = encProcess::get_instance();

		// ajax actions
		add_action('wp_ajax_nwp_track_order', array($this, 'check_order_status'));
		add_action('wp_ajax_nwp_create_invoice', array($this, 'create_invoice_manual'));
		add_action('woocommerce_order_refunded', array($this, 'credit_close_refund'));
	}

	/**
	 * check order status
	 */
	public function check_order_status()
	{
		$data = sanitize_text_field($_POST['value']);

		$order = wc_get_order($data);   // 原$_REQUEST['order-received']

		// 查詢交易API
		$amount = round($order->get_total());

		$result = $this->query_trade_info($data, $amount);

		if (!empty($result)) {
			$trade_status = $result['Result']['TradeStatus'];

			switch ($trade_status) {
				case '0':
					$status = __('交易狀態:未付款', 'newebpay-payment');
					break;
				case '1':
					$order->payment_complete();
					$status = __('交易狀態:交易成功', 'newebpay-payment');

					$note_text  = '<<<code>' . __('藍新金流', 'newebpay-payment') . '</code>>>';
					$note_text .= '</br>' . __('商店訂單編號：', 'newebpay-payment') . $data;
					$note_text .= '</br>' . __('藍新金流支付方式：', 'newebpay-payment') . $this->get_payment_type_str($result['Result']['PaymentType'], !empty($result['Result']['P2GPaymentType']));
					$note_text .= '</br>' . __('藍新金流交易序號：', 'newebpay-payment') . $result['Result']['TradeNo'];
					$order->add_order_note($note_text);

					break;

				case '2':
					$status = __('交易狀態:付款失敗', 'newebpay-payment');
					break;

				case '3':
					$status = __('交易狀態:取消付款', 'newebpay-payment');
					break;
			}

			$echo_str = __('藍新金流交易序號:', 'newebpay-payment') . esc_attr($result['Result']['TradeNo']) . PHP_EOL .
				__('商店支付方式:', 'newebpay-payment') . esc_attr($result['Result']['PaymentType']) . PHP_EOL . $status;
		} else {
			$echo_str = __('此功能僅支援藍新金流訂單使用', 'newebpay-payment');
		}

		wp_die($echo_str);
	}

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
	 * close the credit transaction (refund)
	 */
	public function credit_close_refund()
	{
		foreach ($_POST as $key => $value) {
			$data[$key] = sanitize_text_field($value);
		}

		$order_id = $data['order_id'];

		$order = wc_get_order($order_id);   // 原$_REQUEST['order-received']

		// 查詢交易API
		$amount = round($order->get_total());

		$result        = $this->query_trade_info($order_id, $amount);
		$payment_type  = $result['Result']['PaymentType'];
		$close_status  = $result['Result']['CloseStatus'];
		$refund_amount = $data['refund_amount'];

		$note_text  = '<<<code>藍新金流信用卡退款</code>>>';
		$note_text .= '</br>商店訂單編號：' . $order_id;
		$note_text .= '</br>藍新金流交易序號：' . $result['Result']['TradeNo'];

		if ($payment_type == 'CREDIT') {

			switch ($close_status) {
				case '0':
					$note_text .= '</br>本次交易尚未請款';
					break;
				case '1':
					$note_text .= '</br>本次交易請款處理中';
					break;
				case '2':
					$note_text .= '</br>本次交易請款處理中';
					break;
				case '3':
					$api_url = $this->creditClose;

					$query = array(
						'RespondType'     => 'JSON',
						'Version'         => '1.1',
						'Amt'             => $refund_amount,
						'MerchantOrderNo' => $order_id,
						'TimeStamp'       => time(),
						'IndexType'       => ($order_id == '') ? '2' : '1',
						'TradeNo'         => $result['Result']['TradeNo'],
						'CloseType'       => '2',
					);

					$aes = $this->encProcess->create_mpg_aes_encrypt($query, $this->HashKey, $this->HashIV);

					$post_data = http_build_query(
						array(
							'body' => array(
								'MerchantID_' => $this->MerchantID,
								'PostData_'   => $aes,
							),
						)
					);

					$close_result  = wp_remote_post($api_url, $post_data);
					$respondDecode = json_decode($close_result['body'], true);

					if ($respondDecode['Status'] == 'SUCCESS') {
						$note_text .= '</br>' . __('本次退款金額：', 'newebpay-payment') . $refund_amount . '</br>' . __('退款狀態:退款請求成功', 'newebpay-payment');
					} else {
						$note_text .= '</br>' . __('本次退款金額：', 'newebpay-payment') . $refund_amount . '</br>' . __('退款狀態:退款請求失敗,錯誤代碼', 'newebpay-payment') . $respondDecode['Status'] . '</br>' . __('請至藍新官網查詢', 'newebpay-payment');
					}

					break;
			}

			$order->add_order_note(__($note_text, 'newebpay-payment'));

			return $respondDecode;
		}
	}

	/**
	 * call nwp queryTradeInfo api
	 */
	private function query_trade_info($order_id, $amount)
	{
		$api_url = $this->queryTrade;
		$order = wc_get_order($order_id);
		$merchant_order_no = $order->get_meta('_newebpayMerchantOrderNo');

		$check_value_arr = array(
			'IV'              => $this->HashIV,
			'Amt'             => $amount,
			'MerchantID'      => $this->MerchantID,
			'MerchantOrderNo' => $merchant_order_no,
			'Key'             => $this->HashKey,
		);

		$check_value = strtoupper(hash('sha256', http_build_query($check_value_arr)));

		$post_data = array(
			'body' => array(
				'MerchantID'      => $this->MerchantID,
				'Version'         => '1.1',
				'RespondType'     => 'JSON',
				'CheckValue'      => $check_value,
				'TimeStamp'       => time(),
				'MerchantOrderNo' => $merchant_order_no,
				'Amt'             => $amount,
			),
		);
		// $result        = wp_remote_post( $api_url, $post_data );
		// $respondDecode = json_decode( $result['body'], true );

		$post_str = $post_data['body'];

		// curl 結果
		$result = $this->curl_($api_url, http_build_query($post_str));
		$respondDecode = json_decode($result['web_info'], true);

		return $respondDecode;
	}

	//curl 函式
	private function curl_($curl_url, $curl_str = '')
	{
		//curl init
		$ch = curl_init();
		//curl set option
		curl_setopt($ch, CURLOPT_URL, $curl_url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_str);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		//execute
		$result = curl_exec($ch);
		$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$curl_error = curl_errno($ch);
		//close
		curl_close($ch);

		$return_array = [
			'url' => $curl_url,
			'send_parameter' => $curl_str,
			'http_status' => $retcode,
			'curl_error_no' => $curl_error,
			'web_info' => $result
		];

		return $return_array;
	}

	/**
	 * create invoice manually
	 */
	public function create_invoice_manual()
	{
		foreach ($_POST as $key => $value) {
			$data[$key] = sanitize_text_field($value);
		}

		$order_id = $data['value'];
		$order    = wc_get_order($order_id);
		$amount   = $order->get_total();

		$check_result = $this->query_trade_info($order_id, $amount);

		if ($check_result['Status'] == 'SUCCESS') {
			$trade_no = $check_result['Result']['TradeNo'];

			if ($this->inv_status == 'yes') {
				$inv_checkout = $this->inv->electronic_invoice($order, $trade_no);

				if ($inv_checkout->Status == 'SUCCESS') {
					$echo_str = __('發票開立成功,回應訊息:', 'newebpay-payment') . sanitize_text_field($inv_checkout->Message);
				} else {
					// 檢查是否為重複開立發票的情況
					if (strpos($inv_checkout->Message, '該筆自訂單號已重覆開立發票') !== false) {
						// 如果是重複開立，只添加訂單備註，不顯示錯誤訊息
						$order->add_order_note(__('發票處理結果：發票已存在，無需重複開立', 'newebpay-payment'));
						$echo_str = __('發票已存在，無需重複開立', 'newebpay-payment');
					} else {
						$echo_str = __('發票開立失敗<br>回應訊息：', 'newebpay-payment') . $inv_checkout->Message;
					}
				}
			} else {
				$echo_str = __('您未啟用藍新電子發票', 'newebpay-payment');
			}
		} else {
			$echo_str = __('藍新平台查無此交易,請聯繫藍新金流客服', 'newebpay-payment');
		}

		wp_die($echo_str);
	}
}

new nwpOthersAPI();
