<?php



if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class nwpElectronicInvoice {

	private $eichk;
	private $invMerchantID;
	private $invMerchantIV;
	private $taxType;
	private $eiStatus;
	private $createStatusTime;
	private $notifyUrl;
	
	/**
	 * Invoice Hash Key
	 * @var string
	 */
	public $invHashKey;

	/**
	 * Invoice Hash IV
	 * @var string
	 */
	public $invHashIV;

	/**
	 * Test Mode
	 * @var string
	 */
	public $testMode;

	/**
	 * Encryption Process instance
	 * @var encProcess
	 */
	public $encProcess;

	private static $instance;

	/**
	 * Returns the *Singleton* instance of this class.
	 *
	 * @return Singleton The *Singleton* instance.
	 */
	public static function get_instance( $invData = array() ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $invData );
		}
		return self::$instance;
	}

	protected function __construct( $invData = array() ) {
		$this->eichk            = $invData['eiChk'];
		$this->invMerchantID    = $invData['invMerchantID'];
		$this->invHashKey       = $invData['invHashKey'];
		$this->invHashIV        = $invData['invHashIV'];
		$this->taxType          = $invData['taxType'];
		$this->eiStatus         = $invData['eiStatus'];
		$this->createStatusTime = $invData['createStatusTime'];
		$this->testMode         = $invData['testMode'];

		$this->encProcess = encProcess::get_instance();

		add_action( 'woocommerce_after_checkout_form', array( $this, 'invoice_checkout' ) );
		add_action( 'woocommerce_after_order_notes', array( $this, 'electronic_invoice_fields' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'electronic_invoice_fields_update_order_meta' ) );
	}

	function electronic_invoice( $order, $tradeNum ) {
		if ( $this->testMode == 'yes' ) {
			$url = 'https://cinv.ezpay.com.tw/API/invoice_issue'; // 測試網址
		} else {
			$url = 'https://inv.ezpay.com.tw/API/invoice_issue'; // 正式網址
		}
		$MerchantID = $this->invMerchantID; // 商店代號
		$key        = $this->invHashKey;  // 商店專屬串接金鑰HashKey值
		$iv         = $this->invHashIV;  // 商店專屬串接iv

		$order_id             = $order->get_id();
		$status               = $this->eiStatus;
		$createStatusTime     = (int) $this->createStatusTime;
		$createStatusTime     = date( 'Y-m-d', time() + ( $createStatusTime * 86400 ) ); // 加上預約開立時間
		$discount_with_no_tax = $order->get_total_discount();
		$discount_with_tax    = $order->get_total_discount( false );
		// $_tax = new WC_Tax();
		// 商品資訊
		$item_name = $order->get_items();
		$item_cnt  = 1;
		// $itemPriceSum = 0;

		// $buyerNeedUBN = get_post_meta( $order_id, '_billing_needUBN', true );
		$buyerNeedUBN = $order->get_meta( '_billing_needUBN' );
		if ( $buyerNeedUBN ) {
			// $buyerUBN    = get_post_meta( $order_id, '_billing_UBN', true );
			$buyerUBN    = $order->get_meta( '_billing_UBN' );
			$category    = 'B2B';
			$invoiceFlag = -1;
		} else {
			$buyerUBN    = '';
			$category    = 'B2C';
			// $invoiceFlag = get_post_meta( $order_id, '_billing_invoiceFlag', true );
			$invoiceFlag = $order->get_meta( '_billing_invoiceFlag' );
		}
		$itemName  = '';
		$itemCount = '';
		$itemUnit  = '';
		$itemPrice = '';
		$itemAmt   = '';
		foreach ( $item_name as $keyx => $item_value ) {
			// $pid 若取的到variation_id為可變商品 取到0為非可變商品
			$pid       = ( empty( $item_name[ $keyx ]->get_variation_id() ) ) ? $item_name[ $keyx ]->get_product_id() : $item_name[ $keyx ]->get_variation_id();
			$tax_class = $item_name[ $keyx ]->get_tax_class();
			// $item_count = $item_name[$keyx]['qty'];
			$product = wc_get_product( $pid );
			// $product = new WC_Product($pid);
			@$rates_data = array_shift( WC_Tax::get_rates( $tax_class ) );  // array_shift($_tax->get_rates($product->get_tax_class()));
			$taxRate     = (float) $rates_data['rate']; // 取得稅率

			if ( ! $this->chkProductInvCategoryisValid( $product, $category ) ) {
				$orderNote = '發票開立失敗<br>錯誤訊息：' . '無法取得商品資訊';
				$order->add_order_note( __( $orderNote, 'newebpay-payment' ) );
				exit();
			}

			if ( $item_cnt != count( $item_name ) ) {
				$itemName  .= $item_value->get_name() . '|';
				$itemCount .= $item_value->get_quantity() . '|';
				$itemUnit  .= '個|';
				$itemPrice .= $this->getProductPriceByCategory( $product, $category ) . '|';
				$itemAmt   .= $this->getProductPriceByCategory( $product, $category ) * $item_value['qty'] . '|';
			} elseif ( $item_cnt == count( $item_name ) ) {
				$itemName  .= $item_value->get_name();
				$itemCount .= $item_value->get_quantity();
				$itemUnit  .= '個';
				$itemPrice .= $this->getProductPriceByCategory( $product, $category );
				$itemAmt   .= $this->getProductPriceByCategory( $product, $category ) * $item_value['qty'];
			}
			// $itemPriceSum += $itemAmtRound;

			$item_cnt++;
		}

		if ( ! $this->chkOrderInvCategoryisValid( $order, $category ) ) {
			$orderNote = '發票開立失敗<br>錯誤訊息：' . '無法取得訂單資訊';
			$order->add_order_note( __( $orderNote, 'newebpay-payment' ) );
			exit();
		}

		if ( $order->get_total_shipping() > 0 ) {
			$itemName  .= '|' . $order->get_shipping_method();
			$itemCount .= '|1';
			$itemUnit  .= '|個';
			$itemPrice .= '|' . $this->getShippingPriceByCategory( $order, $category );
			$itemAmt   .= '|' . $this->getShippingPriceByCategory( $order, $category );
		}

		if ( $discount_with_tax > 0 ) {
			$itemName  .= '|' . '折扣';
			$itemCount .= '|1';
			$itemUnit  .= '|次';
			$itemPrice .= '|-' . $discount_with_tax;
			$itemAmt   .= '|-' . $discount_with_tax;
		}

		$amt      = round( $order->get_total() ) - round( $order->get_total_tax() );
		$taxAmt   = round( $order->get_total_tax() );
		$totalAmt = round( $order->get_total() );

		$customsClearance = null;
		$taxType          = $this->taxType;

		switch ( $taxType ) {
			case 2.1:
				$taxType          = 2;
				$customsClearance = 1;
				break;
			case 2.2:
				$taxType          = 2;
				$customsClearance = 2;
				break;
		}

		// $buyerName    = get_post_meta( $order_id, '_billing_Buyer', true );  // B2B可輸入買受人名稱 若無輸入就使用帳單的姓名(B2C直接用這個)
		$buyerName    = $order->get_meta( '_billing_Buyer' );
		$buyerName    = ( empty( $buyerName ) ) ? $order->get_billing_last_name() . ' ' . $order->get_billing_first_name() : $buyerName;
		$buyerEmail   = $order->get_billing_email();
		$buyerAddress = $order->get_billing_postcode() . $order->get_billing_state() . $order->get_billing_city() . $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
		$buyerComment = $order->get_customer_note();

		// $invoiceFlagNum = get_post_meta( $order_id, '_billing_invoiceFlagNum', true );
		$invoiceFlagNum = $order->get_meta( '_billing_invoiceFlagNum' );

		switch ( $invoiceFlag ) {
			case -1:
				$printFlag   = 'Y';
				$carruerType = '';
				$carruerNum  = '';
				$loveCode    = '';
				break;
			case 0:
				$printFlag   = 'N';
				$carruerType = 0;
				$carruerNum  = $invoiceFlagNum;
				$loveCode    = '';
				break;
			case 1:
				$printFlag   = 'N';
				$carruerType = 1;
				$carruerNum  = $invoiceFlagNum;
				$loveCode    = '';
				break;
			case 2:
				$printFlag   = 'N';
				$carruerType = 2;
				$carruerNum  = $buyerEmail;
				$loveCode    = '';
				break;
			case 3:
				$printFlag   = 'N';
				$carruerType = '';
				$carruerNum  = '';
				$loveCode    = $invoiceFlagNum;
				break;
			default:
				$printFlag   = 'N';
				$carruerType = 2;
				$carruerNum  = $buyerEmail;
				$loveCode    = '';
		}
		$post_data_array = array( // post_data欄位資料
			'RespondType'      => 'JSON',
			'Version'          => '1.4',
			'TimeStamp'        => time(),
			'TransNum'         => $tradeNum,
			'MerchantOrderNo'  => $order->get_meta( '_newebpayMerchantOrderNo' ),
			'Status'           => $status,
			'CreateStatusTime' => $createStatusTime,
			'Category'         => $category,
			'BuyerName'        => $buyerName,
			'BuyerUBN'         => $buyerUBN,
			'BuyerAddress'     => $buyerAddress,
			'BuyerEmail'       => $buyerEmail,
			'CarrierType'      => $carruerType,
			'CarrierNum'       => $carruerNum,
			'LoveCode'         => $loveCode,
			'PrintFlag'        => $printFlag,
			'TaxType'          => $taxType,
			'CustomsClearance' => $customsClearance,
			'TaxRate'          => $taxRate,
			'Amt'              => $amt,
			'TaxAmt'           => $taxAmt,
			'TotalAmt'         => $totalAmt,
			'ItemName'         => $itemName,
			'ItemCount'        => $itemCount,
			'ItemUnit'         => $itemUnit,
			'ItemPrice'        => $itemPrice,
			'ItemAmt'          => $itemAmt,
			'Comment'          => $buyerComment,
		);

		// $post_data = trim(bin2hex(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $this->addpadding($post_data_str), MCRYPT_MODE_CBC, $iv))); //加密
		$post_data              = $this->encProcess->create_mpg_aes_encrypt( $post_data_array, $key, $iv ); // trim(bin2hex(openssl_encrypt($this->encPraddpadding($post_data_str), 'aes-256-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv))); //加密
		$transaction_data_array = array( // 送出欄位
			'body' => array(
				'MerchantID_' => $MerchantID,
				'PostData_'   => $post_data,
				'CartVersion' => 'New_WooCommerce_1_0_1',
			),
		);
		$result                 = wp_remote_post( $url, $transaction_data_array ); // 背景送出
		
		// 檢查 wp_remote_post 是否返回錯誤
		if ( is_wp_error( $result ) ) {
			$orderNote = '發票開立失敗<br>網路連線錯誤：' . $result->get_error_message();
			$order->add_order_note( __( $orderNote, 'newebpay-payment' ) );
			
			// 返回一個模擬的錯誤回應對象
			$respondDecode = (object) array(
				'Status' => 'ERROR',
				'Message' => '網路連線錯誤：' . $result->get_error_message()
			);
			return $respondDecode;
		}
		
		// Add order notes on admin
		$respondDecode = json_decode( $result['body'] );
		
		// 檢查 JSON 解析是否成功
		if ( $respondDecode === null ) {
			$orderNote = '發票開立失敗<br>回應格式錯誤：無法解析伺服器回應';
			$order->add_order_note( __( $orderNote, 'woothemes' ) );
			
			// 返回一個模擬的錯誤回應對象
			$respondDecode = (object) array(
				'Status' => 'ERROR',
				'Message' => '回應格式錯誤：無法解析伺服器回應'
			);
			return $respondDecode;
		}
		
		if ( in_array( $respondDecode->Status, array( 'SUCCESS', 'CUSTOM' ) ) ) {
			$resultDecode   = json_decode( $respondDecode->Result );
			$invoiceTransNo = isset( $resultDecode->InvoiceTransNo ) ? $resultDecode->InvoiceTransNo : '';
			$invoiceNumber  = isset( $resultDecode->InvoiceNumber ) ? $resultDecode->InvoiceNumber : '';
			$message        = isset( $respondDecode->Message ) ? $respondDecode->Message : '發票開立成功';
			$orderNote      = $message . '<br>ezPay開立序號: ' . $invoiceTransNo . '<br>' . '發票號碼: ' . $invoiceNumber;
		} else {
			$message = isset( $respondDecode->Message ) ? $respondDecode->Message : '未知錯誤';
			// 檢查是否為重複開立發票的錯誤
			if ( strpos( $message, '該筆自訂單號已重覆開立發票' ) !== false ) {
				$orderNote = '發票處理結果：發票已存在（該筆自訂單號已重覆開立發票）';
			} else {
				$orderNote = '發票開立失敗<br>錯誤訊息：' . $message;
			}
		}
		$order->add_order_note( __( $orderNote, 'newebpay-payment' ) );

		return $respondDecode;
	}


	private function chkOrderInvCategoryisValid( $order, $category ) {
		if ( ! isset( $order ) ) {
			return false;
		}
		if ( ! isset( $category ) ) {
			return false;
		}
		return true;
	}

	private function chkProductInvCategoryisValid( $product, $category ) {
		if ( ! isset( $product ) ) {
			return false;
		}
		if ( ! isset( $category ) ) {
			return false;
		}
		return true;
	}


	/**
	 * 依照發票類型取得單一產品價格
	 *
	 * @access public
	 * @param product $product , string $category
	 * @return float|boolean
	 */
	public function getProductPriceByCategory( $product, $category ) {
		switch ( $category ) {
			case 'B2B':
				return round( wc_get_price_including_tax( $product ), 2 );
				break;

			case 'B2C':
				return round( $product->get_price(), 2 ); // 含稅價
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * 依照發票類型取得運費價格
	 *
	 * @access public
	 * @param order $order , string $category
	 * @return float|boolean
	 */
	public function getShippingPriceByCategory( $order, $category ) {
		switch ( $category ) {
			case 'B2B':
				return round( $order->get_total_shipping() );
				break;

			case 'B2C':
				return round( $order->get_total_shipping() + $order->get_shipping_tax() ); // 含稅價
				break;
			default:
				return false;
				break;
		}
	}

	public function electronic_invoice_fields( $checkout ) {
		$eiChk = $this->eichk;
		if ( $eiChk == 'yes' ) {
			echo "<div id='electronic_invoice_fields'><h3>" . __('發票資訊', 'newebpay-payment') . "</h3>";
			woocommerce_form_field(
				'billing_needUBN',
				array(
					'type'    => 'select',
					'label'   => __( '發票是否需要打統一編號', 'newebpay-payment' ),
					'options' => array(
						'0' => __( '否', 'newebpay-payment' ),
						'1' => __( '是', 'newebpay-payment' ),
					),
				),
				$checkout->get_value( 'billing_needUBN' )
			);

			echo "<div id='buDiv'>";
			woocommerce_form_field(
				'billing_UBN',
				array(
					'type'        => 'text',
					'label'       => __( '<div id="UBNdiv" style="display:inline;">統一編號</div><div id="UBNdivAlert" style="display:none;color:#FF0000;">&nbsp&nbsp格式錯誤!!!</div></p>', 'newebpay-payment' ),
					'placeholder' => __( '請輸入統一編號', 'newebpay-payment' ),
					'required'    => false,
					'default'     => '',
				),
				$checkout->get_value( 'billing_UBN' )
			);
			woocommerce_form_field(
				'billing_Buyer',
				array(
					'type'        => 'text',
					'label'       => __( '<div id="Buyerdiv" style="display:inline;">買受人名稱</div>', 'newebpay-payment' ),
					'placeholder' => __( '請輸入買受人名稱', 'newebpay-payment' ),
					'required'    => false,
					'default'     => '',
				),
				$checkout->get_value( 'billing_Buyer' )
			);
			echo __('電子發票將寄送至您的電子郵件地址，請自行列印。', 'newebpay-payment') . '</div>';

			echo "<div id='bifDiv'>";
			woocommerce_form_field(
				'billing_invoiceFlag',
				array(
					'type'    => 'select',
					'label'   => __( '電子發票索取方式', 'newebpay-payment' ),
					'options' => array(
						'2'  => __( '會員載具', 'newebpay-payment' ),
						'0'  => __( '手機條碼', 'newebpay-payment' ),
						'1'  => __( '自然人憑證條碼', 'newebpay-payment' ),
						'3'  => __( '捐贈發票', 'newebpay-payment' ),
						'-1' => __( '索取紙本發票', 'newebpay-payment' ),
					),
				),
				$checkout->get_value( 'billing_invoiceFlag' )
			);
			echo '</div>';

			echo "<div id='bifnDiv' style='display:none;'>";
			woocommerce_form_field(
				'billing_invoiceFlagNum',
				array(
					'type'        => 'text',
					'label'       => __( '<div id="ifNumDiv">載具編號</div>', 'newebpay-payment' ),
					'placeholder' => __( '電子發票通知將寄送至您的電子郵件地址', 'newebpay-payment' ),
					'required'    => false,
					'default'     => '',
				),
				$checkout->get_value( 'billing_invoiceFlagNum' )
			);
			echo '</div>';
			echo "<div id='bifnDivAlert' style='display:none;color:#FF0000;'>" . __('請輸入載具編號', 'newebpay-payment') . "</div>";
			echo '</div>';
		}
		return $checkout;
	}

	public function invoice_checkout() {
		// 引用js
		wp_enqueue_script(
			'newebpay_invoice_setting',
			plugins_url( 'assets/js/public/newebpayInvoiceSetting.js', dirname( dirname( __FILE__ ) ) ),
			array( 'jquery' )
		);
	}

	public function electronic_invoice_fields_update_order_meta( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! in_array( sanitize_text_field( $_POST['payment_method'] ), array( 'newebpay', 'spgateway' ) ) && $this->eiChk == 'yes' ) {
			$orderNote = '此訂單尚未開立電子發票，如確認收款完成須開立發票，請至ezPay電子發票平台進行手動單筆開立。<br>發票資料如下<br>發票是否需要打統一編號： ';
			if ( sanitize_text_field( $_POST['billing_needUBN'] ) ) {
				$orderNote .= '是<br>';
				$orderNote .= '統一編號： ' . sanitize_text_field( $_POST['billing_UBN'] );
			} else {
				$invoiceFlag    = sanitize_text_field( $_POST['billing_invoiceFlag'] );
				$invoiceFlagNum = sanitize_text_field( $_POST['billing_invoiceFlagNum'] );
				$orderNote     .= '否<br>電子發票索取方式： ';
				switch ( $invoiceFlag ) {
					case -1:
						$orderNote .= '索取紙本發票';
						break;
					case 0:
						$orderNote .= '手機條碼 <br>載具編號： ' . $invoiceFlagNum;
						break;
					case 1:
						$orderNote .= '自然人憑證條碼 <br>載具編號： ' . $invoiceFlagNum;
						break;
					case 2:
						$invoiceFlagNum = sanitize_text_field( $_POST['billing_email'] );
						$orderNote     .= '會員載具 <br>載具編號： ' . $invoiceFlagNum;
						break;
					case 3:
						$orderNote .= '捐贈發票 <br>捐贈碼： ' . $invoiceFlagNum;
						break;
					default:
						$orderNote .= '會員載具 <br>載具編號： ' . $invoiceFlagNum;
				}
			}
			$order->add_order_note( __( $orderNote, 'newebpay-payment' ) );
		}

		// Hidden Custom Fields: keys starting with an "_".
		update_post_meta( $order_id, '_billing_needUBN', sanitize_text_field( $_POST['billing_needUBN'] ) );
		update_post_meta( $order_id, '_billing_UBN', sanitize_text_field( $_POST['billing_UBN'] ) );
		update_post_meta( $order_id, '_billing_invoiceFlag', sanitize_text_field( $_POST['billing_invoiceFlag'] ) );
		update_post_meta( $order_id, '_billing_invoiceFlagNum', sanitize_text_field( $_POST['billing_invoiceFlagNum'] ) );
		update_post_meta( $order_id, '_billing_Buyer', sanitize_text_field( $_POST['billing_Buyer'] ) );
	}
}
