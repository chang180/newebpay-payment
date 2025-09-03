<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'wc_newebpay_setting',
	array(
		'enabled'                    => array(
			'title'   => __( '啟用/關閉', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '啟動 藍新金流 收款模組', 'woocommerce' ),
			'default' => 'yes',
		),
		'TestMode'                   => array(
			'title'   => __('測試模組', 'woocommerce'),
			'type'    => 'checkbox',
			'label'   => __('啟動測試模組', 'woocommerce'),
			'default' => 'yes',
		),
		'title'                      => array(
			'title'       => __( '標題', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '客戶在結帳時所看到的標題', 'woocommerce' ),
			'default'     => __( '藍新金流Newebpay第三方金流平台', 'woocommerce' ),
		),
		'LangType'                   => array(
			'title'   => __( '支付頁語系', 'woocommerce' ),
			'type'    => 'select',
			'options' => array(
				'zh-tw' => '中文',
				'en'    => 'En',
			),
		),
		'description'                => array(
			'title'       => __( '客戶訊息', 'woocommerce' ),
			'type'        => 'textarea',
			'description' => __( '', 'woocommerce' ),
			'default'     => __( '透過 藍新金流 付款。<br>會連結到 藍新金流 頁面。', 'woocommerce' ),
		),
		'MerchantID'                 => array(
			'title'       => __( '藍新金流商店 Merchant ID', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '請填入您的藍新金流商店代號', 'woocommerce' ),
		),
		'HashKey'                    => array(
			'title'       => __( '藍新金流商店 Hash Key', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '請填入您的藍新金流的HashKey', 'woocommerce' ),
		),
		'HashIV'                     => array(
			'title'       => __( '藍新金流商店 Hash IV', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '請填入您的藍新金流的HashIV', 'woocommerce' ),
		),
		'ExpireDate'                 => array(
			'title'       => __( '繳費有效期限(天)', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '請設定繳費有效期限(1~180天), 預設為7天', 'woocommerce' ),
			'default'     => 7,
		),
		'NwpPaymentMethodCredit'     => array(
			'title'   => __( '信用卡一次付清', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '信用卡一次付清', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodAndroidPay' => array(
			'title'   => __( 'Google Pay', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Google Pay', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodSamsungPay' => array(
			'title'   => __( 'Samsung Pay', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Samsung Pay', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodLinePay'    => array(
			'title'   => __( 'Line Pay', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Line Pay', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodInst'       => array(
			'title'   => __( '信用卡分期付款', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '信用卡分期付款', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodCreditRed'  => array(
			'title'   => __( '信用卡紅利', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '信用卡紅利', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodUnionPay'   => array(
			'title'   => __( '銀聯卡', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '銀聯卡', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodWebatm'     => array(
			'title'   => __( 'WEBATM', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'WEBATM', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodVacc'       => array(
			'title'   => __( 'ATM轉帳', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'ATM轉帳', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodCVS'        => array(
			'title'   => __( '超商代碼', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '超商代碼', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodBARCODE'    => array(
			'title'   => __( '超商條碼', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '超商條碼', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodEsunWallet' => array(
			'title'   => __( '玉山Wallet', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '玉山Wallet', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodTaiwanPay'  => array(
			'title'   => __( '台灣Pay', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '台灣Pay', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodBitoPay'  => array(
			'title'   => __( 'BitoPay', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'BitoPay', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodEZPWECHAT'  => array(
			'title'   => __( '微信支付', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '微信支付', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodEZPALIPAY'  => array(
			'title'   => __( '支付寶', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '支付寶', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodAPPLEPAY'  => array(
			'title'   => __( 'Apple Pay', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'Apple Pay', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodSmartPay'  => array(
			'title'   => __( '智慧ATM2.0', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '智慧ATM2.0', 'woocommerce' ),
			'description' => __( '智慧ATM2.0 需要設定 SourceType、SourceBankId、SourceAccountNo 三個參數（需聯繫藍新金流申請）', 'woocommerce' ),
			'default' => 'no',
			'desc_tip' => true,
		),
		'SmartPaySourceType'  => array(
			'title'   => __( '智慧ATM2.0 - SourceType', 'woocommerce' ),
			'type'    => 'text',
			'default' => '',
		),
		'SmartPaySourceBankId'  => array(
			'title'   => __( '智慧ATM2.0 - SourceBankId', 'woocommerce' ),
			'type'    => 'text',
			'default' => '',
		),
		'SmartPaySourceAccountNo'  => array(
			'title'   => __( '智慧ATM2.0 - SourceAccountNo', 'woocommerce' ),
			'type'    => 'text',
			'default' => '',
		),
		'NwpPaymentMethodTWQR'  => array(
			'title'   => __( 'TWQR', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( 'TWQR', 'woocommerce' ),
			'default' => 'no',
		),
		'NwpPaymentMethodCVSCOMPayed'  => array(
			'title'   => __('超商取貨付款', 'woocommerce'),
			'type'    => 'checkbox',
			'label'   => __('超商取貨付款', 'woocommerce'),
			'default' => 'no',
		),
		'NwpPaymentMethodCVSCOMNotPayed'  => array(
			'title'   => __('超商取貨不付款', 'woocommerce'),
			'type'    => 'checkbox',
			'label'   => __('超商取貨不付款', 'woocommerce'),
			'default' => 'no',
		),
		'eiChk'                      => array(
			'title'   => __( 'ezPay電子發票', 'woocommerce' ),
			'type'    => 'checkbox',
			'label'   => __( '開立電子發票', 'woocommerce' ),
			'default' => 'no',
		),

		'InvMerchantID'              => array(
			'title'       => __( 'ezPay電子發票 Merchant ID', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '請填入您的電子發票商店代號', 'woocommerce' ),
		),
		'InvHashKey'                 => array(
			'title'       => __( 'ezPay電子發票 Hash Key', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '請填入您的電子發票的HashKey', 'woocommerce' ),
		),
		'InvHashIV'                  => array(
			'title'       => __( 'ezPay電子發票 Hash IV', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '請填入您的電子發票的HashIV', 'woocommerce' ),
		),
		'TaxType'                    => array(
			'title'   => __( '稅別', 'woocommerce' ),
			'type'    => 'select',
			'options' => array(
				'1'   => '應稅',
				'2.1' => '零稅率-非經海關出口',
				'2.2' => '零稅率-經海關出口',
				'3'   => '免稅',
			),
		),
		'eiStatus'                   => array(
			'title'   => __( '開立發票方式', 'woocommerce' ),
			'type'    => 'select',
			'options' => array(
				'1' => '立即開立發票',
				'3' => '預約開立發票',
			),
		),
		'CreateStatusTime'           => array(
			'title'       => __( '延遲開立發票(天)', 'woocommerce' ),
			'type'        => 'text',
			'description' => __( '此參數在"開立發票方式"選擇"預約開立發票"才有用', 'woocommerce' ),
			'default'     => 7,
		),
	)
);
