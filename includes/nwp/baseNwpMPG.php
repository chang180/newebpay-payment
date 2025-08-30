<?php

class baseNwpMPG extends WC_Payment_Gateway {


	public function base_action_init() {
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_wc_' . $this->id, array( $this, 'receive_response' ) ); // api_"class名稱(小寫)"
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'admin_other_field' ) );
	}
}
