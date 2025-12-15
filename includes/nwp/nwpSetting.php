<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return apply_filters(
	'wc_newebpay_setting',
	array(
		'enabled'                    => array(
			'title'   => __( '啟用/關閉', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '啟動 藍新金流 收款模組', 'newebpay-payment' ),
			'default' => 'yes',
		),
		'TestMode'                   => array(
			'title'   => __('測試模組', 'newebpay-payment'),
			'type'    => 'checkbox',
			'label'   => __('啟動測試模組', 'newebpay-payment'),
			'default' => 'yes',
		),
		'title'                      => array(
			'title'       => __( '標題', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '客戶在結帳時所看到的標題', 'newebpay-payment' ),
			'default'     => __( '藍新金流Newebpay第三方金流平台', 'newebpay-payment' ),
		),
		'LangType'                   => array(
			'title'   => __( '支付頁語系', 'newebpay-payment' ),
			'type'    => 'select',
			'options' => array(
				'zh-tw' => __( '中文', 'newebpay-payment' ),
				'en'    => __( 'En', 'newebpay-payment' ),
			),
		),
		'description'                => array(
			'title'       => __( '客戶訊息', 'newebpay-payment' ),
			'type'        => 'textarea',
			'description' => __( '', 'newebpay-payment' ),
			'default'     => __( '透過 藍新金流 付款。<br>會連結到 藍新金流 頁面。', 'newebpay-payment' ),
		),
		'MerchantID'                 => array(
			'title'       => __( '藍新金流商店 Merchant ID', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '請填入您的藍新金流商店代號', 'newebpay-payment' ),
		),
		'HashKey'                    => array(
			'title'       => __( '藍新金流商店 Hash Key', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '請填入您的藍新金流的HashKey', 'newebpay-payment' ),
		),
		'HashIV'                     => array(
			'title'       => __( '藍新金流商店 Hash IV', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '請填入您的藍新金流的HashIV', 'newebpay-payment' ),
		),
		'ExpireDate'                 => array(
			'title'       => __( '繳費有效期限(天)', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '請設定繳費有效期限(1~180天), 預設為7天', 'newebpay-payment' ),
			'default'     => 7,
		),
		'NwpPaymentMethodCredit'     => array(
			'title'   => __( '信用卡一次付清', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '信用卡一次付清', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodAndroidPay' => array(
			'title'   => __( 'Google Pay', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( 'Google Pay', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodSamsungPay' => array(
			'title'   => __( 'Samsung Pay', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( 'Samsung Pay', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodLinePay'    => array(
			'title'   => __( 'Line Pay', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( 'Line Pay', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodInst'       => array(
			'title'   => __( '信用卡分期付款', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '信用卡分期付款', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodInstPeriods' => array(
			'title'       => __( '信用卡分期付款期數', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '可輸入的期數選項：3, 6, 12, 18, 24, 30。格式：用逗號分隔，例如：3,6,12。留空則表示啟用所有期數（InstFlag = 1）。符合藍新金流 API 格式要求。', 'newebpay-payment' ),
			'default'     => '',
			'desc_tip'    => true,
		),
		'NwpPaymentMethodCreditRed'  => array(
			'title'   => __( '信用卡紅利', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '信用卡紅利', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodUnionPay'   => array(
			'title'   => __( '銀聯卡', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '銀聯卡', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodWebatm'     => array(
			'title'   => __( 'WEBATM', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( 'WEBATM', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodVacc'       => array(
			'title'   => __( 'ATM轉帳', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( 'ATM轉帳', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodCVS'        => array(
			'title'   => __( '超商代碼', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '超商代碼', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodBARCODE'    => array(
			'title'   => __( '超商條碼', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '超商條碼', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodEsunWallet' => array(
			'title'   => __( '玉山Wallet', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '玉山Wallet', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodTaiwanPay'  => array(
			'title'   => __( '台灣Pay', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '台灣Pay', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodBitoPay'  => array(
			'title'   => __( 'BitoPay', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( 'BitoPay', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodEZPWECHAT'  => array(
			'title'   => __( '微信支付', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '微信支付', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodEZPALIPAY'  => array(
			'title'   => __( '支付寶', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '支付寶', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodAPPLEPAY'  => array(
			'title'   => __( 'Apple Pay', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( 'Apple Pay', 'newebpay-payment' ),
			'default' => 'no',
		),
	'NwpPaymentMethodSmartPay'  => array(
		'title'   => __( '智慧ATM2.0', 'newebpay-payment' ),
		'type'    => 'checkbox',
		'label'   => __( '智慧ATM2.0', 'newebpay-payment' ),
		'description' => __( '智慧ATM2.0 需要設定 SourceType、SourceBankId、SourceAccountNo 三個參數（需聯繫藍新金流申請）', 'newebpay-payment' ),
		'default' => 'no',
		'desc_tip' => true,
	),
	'SmartPaySourceType'  => array(
		'title'   => __( '智慧ATM2.0 - SourceType', 'newebpay-payment' ),
		'type'    => 'text',
		'default' => '',
	),
	'SmartPaySourceBankId'  => array(
		'title'   => __( '智慧ATM2.0 - SourceBankId', 'newebpay-payment' ),
		'type'    => 'text',
			'default' => '',
		),
		'SmartPaySourceAccountNo'  => array(
			'title'   => __( '智慧ATM2.0 - SourceAccountNo', 'newebpay-payment' ),
			'type'    => 'text',
			'default' => '',
		),
		'NwpPaymentMethodTWQR'  => array(
			'title'   => __( 'TWQR', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( 'TWQR', 'newebpay-payment' ),
			'default' => 'no',
		),
		'NwpPaymentMethodCVSCOMPayed'  => array(
			'title'   => __('超商取貨付款', 'newebpay-payment'),
			'type'    => 'checkbox',
			'label'   => __('超商取貨付款', 'newebpay-payment'),
			'default' => 'no',
		),
		'NwpPaymentMethodCVSCOMNotPayed'  => array(
			'title'   => __('超商取貨不付款', 'newebpay-payment'),
			'type'    => 'checkbox',
			'label'   => __('超商取貨不付款', 'newebpay-payment'),
			'default' => 'no',
		),
		'eiChk'                      => array(
			'title'   => __( 'ezPay電子發票', 'newebpay-payment' ),
			'type'    => 'checkbox',
			'label'   => __( '開立電子發票', 'newebpay-payment' ),
			'default' => 'no',
		),

		'InvMerchantID'              => array(
			'title'       => __( 'ezPay電子發票 Merchant ID', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '請填入您的電子發票商店代號', 'newebpay-payment' ),
		),
		'InvHashKey'                 => array(
			'title'       => __( 'ezPay電子發票 Hash Key', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '請填入您的電子發票的HashKey', 'newebpay-payment' ),
		),
		'InvHashIV'                  => array(
			'title'       => __( 'ezPay電子發票 Hash IV', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '請填入您的電子發票的HashIV', 'newebpay-payment' ),
		),
		'TaxType'                    => array(
			'title'   => __( '稅別', 'newebpay-payment' ),
			'type'    => 'select',
			'options' => array(
				'1'   => '應稅',
				'2.1' => '零稅率-非經海關出口',
				'2.2' => '零稅率-經海關出口',
				'3'   => '免稅',
			),
		),
		'eiStatus'                   => array(
			'title'   => __( '開立發票方式', 'newebpay-payment' ),
			'type'    => 'select',
			'options' => array(
				'1' => '立即開立發票',
				'3' => '預約開立發票',
			),
		),
		'CreateStatusTime'           => array(
			'title'       => __( '延遲開立發票(天)', 'newebpay-payment' ),
			'type'        => 'text',
			'description' => __( '此參數在"開立發票方式"選擇"預約開立發票"才有用', 'newebpay-payment' ),
			'default'     => 7,
		),
	)
);
