jQuery( document ).ready(
	function ($) {
		$( '#checkOrder' ).on(
			'click',
			function () {
				// valueSelect = $( this ).val()
				valueSelect = $( this ).data( "value" );
				if (parseInt( valueSelect ) > 0) {
					var data = {
						'action': 'nwp_track_order',
						'value': valueSelect
					};

					$.blockUI( { message: null } );

					$.post(
						ajaxurl,
						data,
						function(response) {
							alert( response );
							location.reload();
						}
					);
				}
			}
		);
	}
);

jQuery( document ).ready(
	function ($) {
		$( '#createInvoice' ).on(
			'click',
			function () {
				valueSelect = $( this ).data( "value" );
				if (parseInt( valueSelect ) > 0) {
					var data = {
						'action': 'nwp_create_invoice',
						'value': valueSelect,
					};

					$.blockUI( { message: null } );

					$.post(
						ajaxurl,
						data,
						function(response) {
							alert( response );
							location.reload();
						}
					);
				}
			}
		);
	}
);
