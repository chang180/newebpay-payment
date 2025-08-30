function idchk(idvalue) {
	var tmp = new String( "12121241" );
	var sum = 0;
	re      = /^\d{8}$/;
	if ( ! re.test( idvalue )) {
		return false;
	}

	for (i = 0; i < 8; i++) {
		s1   = parseInt( idvalue.substr( i, 1 ) );
		s2   = parseInt( tmp.substr( i, 1 ) );
		sum += cal( s1 * s2 );
	}

	if ( ! valid( sum )) {
		if (idvalue.substr( 6, 1 ) == "7") {
			return (valid( sum + 1 ));
		}
	}

	return (valid( sum ));
}

function valid(n) {
	return (n % 10 == 0) ? true : false;
}

function cal(n) {
	var sum = 0;
	while (n != 0) {
		sum += (n % 10);
		n    = (n - n % 10) / 10;
	}
	return sum;
}

function UBNrog() {
	var rog     = "r";
	var UBN     = 0;
	var tof     = false;
	var needUBN = jQuery( "#billing_needUBN" ).val();
	var UBNval  = jQuery( "#billing_UBN" ).val();
	if (needUBN == 1) {
		jQuery( "#bifnDvi" ).css( "display", "inline" );
		jQuery( "#bifnDivAlert" ).css( "display", "none" );
		tof = idchk( UBNval );
		if (tof == true) {
			rog = "g";
		} else {
			rog = "r";
		}
	} else {
		jQuery( "#ifDivAlert" ).css( "display", "none" );
		jQuery( "#billing_UBN" ).val( "" );
		jQuery( "#billing_Buyer" ).val( "" );
		rog = "g";
	}

	if (rog == "r") {
		jQuery( "#UBNdivAlert" ).css( "display", "inline" );
		if (jQuery( "#billing_UBN" ).val().length == 0) {
			jQuery( "#UBNdivAlert" ).html( "&nbsp&nbsp請輸入統一編號!!!" );
		} else {
			jQuery( "#UBNdivAlert" ).html( "&nbsp&nbsp格式錯誤!!!" );
		}
		jQuery( "#place_order" ).attr( "disabled", true );
		jQuery( "#place_order" ).css( "background-color", "red" );
	} else {
		jQuery( "#UBNdivAlert" ).css( "display", "none" );
		jQuery( "#place_order" ).attr( "disabled", false );
		jQuery( "#place_order" ).css( "background-color", "#1fb25a" );
	}
}

function invoiceFlagChk() {
	var ifVal = jQuery( "#billing_invoiceFlag" ).val();
	buOrBif();
	jQuery( "#billing_invoiceFlagNum" ).val( "" );
	jQuery( "#billing_invoiceFlagNum" ).attr( "disabled", false );
	if (ifVal == -1) {
		jQuery( "#bifnDiv" ).css( "display", "none" );
	} else if (ifVal == 0) {
		jQuery( "#ifNumDiv" ).html( "載具編號" );
		jQuery( "#billing_invoiceFlagNum" ).attr( "placeholder", "請輸入手機條碼" );
	} else if (ifVal == 1) {
		jQuery( "#ifNumDiv" ).html( "載具編號" );
		jQuery( "#billing_invoiceFlagNum" ).attr( "placeholder", "請輸入自然人憑證條碼" );
	} else if (ifVal == 3) {
		jQuery( "#ifNumDiv" ).html( '捐贈碼<a href="https://www.einvoice.nat.gov.tw/APCONSUMER/BTC603W/" target="_blank">查詢捐贈碼</a>' );
			jQuery( "#billing_invoiceFlagNum" ).attr( "placeholder", "請輸入受捐單位捐贈碼" );
	} else {
		jQuery( "#ifNumDiv" ).html( "載具編號" );
		jQuery( "#billing_invoiceFlagNum" ).attr( "placeholder", "電子發票通知將寄送至您的電子郵件地址" );
		jQuery( "#billing_invoiceFlagNum" ).attr( "disabled", true );
	}
	invoiceFlagNumChk();
}

function invoiceFlagNumChk() {
	var ifnVal  = jQuery( "#billing_invoiceFlagNum" ).val();
	var ifVal   = jQuery( "#billing_invoiceFlag" ).val();
	var needUBN = jQuery( "#billing_needUBN" ).val();
	if (needUBN == 0) {
		if (ifnVal || ifVal == 2 || ifVal == -1) {
			jQuery( "#bifnDivAlert" ).css( "display", "none" );
			jQuery( "#place_order" ).attr( "disabled", false );
			jQuery( "#place_order" ).css( "background-color", "#1fb25a" );
		} else {
			jQuery( "#bifnDivAlert" ).css( "display", "" );
			jQuery( "#place_order" ).attr( "disabled", true );
			jQuery( "#place_order" ).css( "background-color", "red" );
			if (ifVal == 3) {
				jQuery( "#bifnDivAlert" ).html( "請輸入捐贈碼" );
			} else {
				jQuery( "#bifnDivAlert" ).html( "請輸入載具編號" );
			}
		}
	}
}

jQuery( document ).ready(
	function () {
		buOrBif();
		jQuery( "#billing_UBN" ).attr( "maxlength", "8" );
		jQuery( "#billing_invoiceFlagNum" ).attr( "disabled", true );
		jQuery( "#billing_UBN" ).keyup(
			function () {
				UBNrog();
				if (jQuery( "#billing_UBN" ).val().length < 8) {
					jQuery( "#UBNdivAlert" ).css( "display", "none" );
				}
				invoiceFlagChk();
			}
		);

		jQuery( "#billing_UBN" ).change(
			function () {
				UBNrog();
				invoiceFlagChk();
			}
		);

		jQuery( "#billing_UBN" ).bind(
			"paste",
			function () {
				setTimeout(
					function () {
						UBNrog();
					},
					100
				);
				invoiceFlagChk();
			}
		);

		jQuery( "#billing_invoiceFlag" ).change(
			function () {
				invoiceFlagChk();
			}
		);

		jQuery( "#billing_invoiceFlagNum" ).keyup(
			function () {
				invoiceFlagNumChk();
			}
		);

		jQuery( "#billing_needUBN" ).change(
			function () {
				setTimeout(
					function () {
						UBNrog();
						buOrBif();
					},
					100
				);
			}
		);

		jQuery( "#billing_invoiceFlagNum" ).css( "width", "100%" );
	}
);

function buOrBif() {
	if (jQuery( "#billing_needUBN" ).val() == 1) {
		jQuery( "#buDiv" ).css( "display", "" );
		jQuery( "#bifDiv" ).css( "display", "none" );
		jQuery( "#bifnDiv" ).css( "display", "none" );
	} else {
		jQuery( "#buDiv" ).css( "display", "none" );
		jQuery( "#bifDiv" ).css( "display", "" );
		jQuery( "#bifnDiv" ).css( "display", "" );
	}
}
