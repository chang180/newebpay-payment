var invalidate = function () {
	jQuery( this ).css( 'border-color', 'red' );
	jQuery( '#' + this.id + '_error_msg' ).show();
	jQuery( 'input[type="submit"]' ).prop( 'disabled', 'disabled' );
},
	validate   = function () {
		jQuery( this ).css( 'border-color', '' );
		jQuery( '#' + this.id + '_error_msg' ).hide();
		jQuery( 'input[type="submit"]' ).prop( 'disabled', '' );
	}

	validate = function () {
		jQuery( this ).css( 'border-color', '' );
		jQuery( '#' + this.id + '_error_msg' ).hide();
		jQuery( 'input[type="submit"]' ).prop( 'disabled', '' );

	}

	jQuery( '#woocommerce_newebpay_eiStatus' )
	.bind(
		'change',
		function (e) {
			switch (parseInt( this.value, 10 )) {
				case 1:
					jQuery( '#woocommerce_newebpay_CreateStatusTime' ).prop( 'disabled', 'disabled' ).css( 'background', 'gray' ).val( '' );
					break;
				case 3:
					jQuery( '#woocommerce_newebpay_CreateStatusTime' ).prop( 'disabled', '' ).css( 'background', '' );
					break;
			}
		}
	)
	.trigger( 'change' );

	jQuery( '#woocommerce_newebpay_ExpireDate, #woocommerce_newebpay_CreateStatusTime' )
	.bind(
		'keypress',
		function (e) {
			if (e.charCode < 48 || e.charCode > 57) {
				return false;
			}
		}
	)
	.bind(
		'blur',
		function (e) {
			if ( ! this.value) {
				validate.call( this );
			}
		}
	);

	jQuery( '#woocommerce_newebpay_CreateStatusTime' )
	.bind(
		'input',
		function (e) {
			if ( ! this.value) {
				validate.call( this );
				return false;
			}

			if (this.value < 1) {
				invalidate.call( this );
			} else {
				validate.call( this );
			}
		}
	)
	.after( '<span style="display: none;color: red;" id="woocommerce_newebpay_CreateStatusTime_error_msg">請輸入1以上的數字</span>' )

	jQuery( '#woocommerce_newebpay_ExpireDate' )
	.bind(
		'input',
		function (e) {
			if ( ! this.value) {
				validate.call( this );
				return false;
			}

			if (this.value < 1 || this.value > 180) {
				invalidate.call( this );

			} else {
				validate.call( this );
			}
		}
	)
	.bind(
		'blur',
		function (e) {
			if ( ! this.value) {
				this.value = 7;
				validate.call( this );
			}
		}
	)
	.after( '<span style="display: none;color: red;" id="woocommerce_newebpay_ExpireDate_error_msg">請輸入範圍內1~180的數字</span>' )
