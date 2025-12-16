/**
 * 移除按鈕預設的 focus 狀態（僅針對 Newebpay 的按鈕）
 */
(function() {
	'use strict';
	
	function removeButtonFocus() {
		// 只處理在 wc-block-order-confirmation-status__actions 內的按鈕
		const actionContainers = document.querySelectorAll('.wc-block-order-confirmation-status__actions');
		
		actionContainers.forEach(function(container) {
			const buttons = container.querySelectorAll('.wc-block-order-confirmation-status__button');
			
			buttons.forEach(function(button) {
				// 如果按鈕有 focus，移除它
				if (document.activeElement === button) {
					button.blur();
				}
			});
		});
	}
	
	// DOM 載入完成後執行
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', removeButtonFocus);
	} else {
		removeButtonFocus();
	}
	
	// 也在 WooCommerce Blocks 初始化後執行
	if (typeof document.addEventListener === 'function') {
		document.addEventListener('wc-blocks_rendered', removeButtonFocus);
	}
})();
