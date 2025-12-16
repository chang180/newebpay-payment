/**
 * 將訂單確認頁面中的 <a> 標籤轉換為 <button> 標籤，並移除按鈕預設的 focus 狀態
 */
(function() {
	'use strict';
	
	/**
	 * 將 <a> 標籤轉換為 <button> 標籤
	 */
	function convertLinksToButtons() {
		const actionContainers = document.querySelectorAll('.wc-block-order-confirmation-status__actions');
		
		actionContainers.forEach(function(container) {
			// 查找所有 <a> 標籤（但排除已經是 button 的情況）
			const links = container.querySelectorAll('a.button');
			
			links.forEach(function(link) {
				// 如果已經轉換過，跳過
				if (link.classList.contains('wc-block-order-confirmation-status__button')) {
					return;
				}
				
				// 取得連結的 href 和文字
				const href = link.getAttribute('href');
				const text = link.textContent.trim();
				
				// 判斷是哪個按鈕類型
				let buttonClass = 'wc-block-order-confirmation-status__button';
				if (href && (href.includes('checkout') || href.includes('payment'))) {
					buttonClass += ' wc-block-order-confirmation-status__button--try-again';
				} else if (href && href.includes('myaccount')) {
					buttonClass += ' wc-block-order-confirmation-status__button--my-account';
				}
				
				// 建立新的 button 元素
				const button = document.createElement('button');
				button.type = 'button';
				button.className = buttonClass;
				button.textContent = text;
				
				// 設定 onclick 事件
				if (href) {
					button.onclick = function() {
						window.location.href = href;
					};
				}
				
				// 替換原來的 <a> 標籤
				link.parentNode.replaceChild(button, link);
			});
		});
	}
	
	/**
	 * 移除按鈕預設的 focus 狀態
	 */
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
	
	/**
	 * 執行所有轉換和處理
	 */
	function processButtons() {
		convertLinksToButtons();
		removeButtonFocus();
	}
	
	// DOM 載入完成後執行
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', processButtons);
	} else {
		processButtons();
	}
	
	// 也在 WooCommerce Blocks 初始化後執行
	if (typeof document.addEventListener === 'function') {
		document.addEventListener('wc-blocks_rendered', processButtons);
	}
	
	// 使用 MutationObserver 監聽動態添加的內容
	if (typeof MutationObserver !== 'undefined') {
		const observer = new MutationObserver(function(mutations) {
			mutations.forEach(function(mutation) {
				if (mutation.addedNodes.length > 0) {
					processButtons();
				}
			});
		});
		
		// 監聽整個文檔的變化
		observer.observe(document.body, {
			childList: true,
			subtree: true
		});
	}
})();
