/**
 * Newebpay Blocks - Frontend JavaScript
 * 
 * @package NewebpayPayment
 * @since 1.0.10
 */

(function($) {
    'use strict';
    
    // 初始化付款方式區塊功能
    const initPaymentMethodsBlocks = function() {
        $('.wp-block-newebpay-payment-methods').each(function() {
            const $block = $(this);
            const $methods = $block.find('.newebpay-payment-method');
            
            // 為付款方式添加點擊事件
            $methods.on('click', function() {
                const $method = $(this);
                const methodKey = $method.data('method');
                
                // 移除其他選擇的狀態
                $methods.removeClass('selected');
                
                // 添加選擇狀態
                $method.addClass('selected');
                
                // 觸發自定義事件
                $block.trigger('newebpay:method-selected', [methodKey]);
            });
            
            // 添加鍵盤支援
            $methods.attr('tabindex', '0').on('keypress', function(e) {
                if (e.which === 13 || e.which === 32) { // Enter 或 Space
                    e.preventDefault();
                    $(this).click();
                }
            });
        });
    };
    
    // 處理付款方式變更事件
    const handlePaymentMethodChange = function() {
        $(document).on('newebpay:method-selected', '.wp-block-newebpay-payment-methods', function(e, methodKey) {
            console.log('Payment method selected:', methodKey);
            
            // 與 WooCommerce 結帳流程整合
            if (typeof wc_checkout_params !== 'undefined') {
                // 在結帳頁面上
                updateWooCommercePaymentMethod(methodKey);
            } else if ($('.woocommerce-cart').length > 0) {
                // 在購物車頁面上
                showPaymentMethodInfo(methodKey);
            } else {
                // 一般頁面，顯示資訊或導向結帳
                showGeneralPaymentInfo(methodKey);
            }
        });
    };
    
    // 更新 WooCommerce 付款方式
    const updateWooCommercePaymentMethod = function(methodKey) {
        // 尋找對應的 WooCommerce 付款方式選項
        const paymentInput = $('input[name="payment_method"][value="nwp"]');
        
        if (paymentInput.length > 0) {
            // 選擇 Newebpay 付款方式
            paymentInput.prop('checked', true).trigger('change');
            
            // 觸發結帳更新
            $('body').trigger('update_checkout');
            
            // 儲存選擇的付款子方式到隱藏欄位或 session
            if ($('#newebpay_selected_method').length === 0) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'newebpay_selected_method',
                    name: 'newebpay_selected_method',
                    value: methodKey
                }).appendTo('form.checkout');
            } else {
                $('#newebpay_selected_method').val(methodKey);
            }
        }
    };
    
    // 顯示付款方式資訊（購物車頁面）
    const showPaymentMethodInfo = function(methodKey) {
        const methodNames = {
            'credit': '信用卡',
            'webatm': '網路ATM',
            'vacc': 'ATM轉帳',
            'cvs': '超商代碼',
            'barcode': '超商條碼',
            'SmartPay': '智慧ATM2.0'
        };
        
        const methodName = methodNames[methodKey] || methodKey;
        
        // 移除之前的提示
        $('.newebpay-payment-info').remove();
        
        // 顯示新的提示
        const infoHtml = `
            <div class="woocommerce-info newebpay-payment-info">
                您選擇了 <strong>${methodName}</strong> 付款方式。
                <a href="${window.location.origin}/checkout" class="button">前往結帳</a>
            </div>
        `;
        
        $('.woocommerce-cart-form').before(infoHtml);
    };
    
    // 顯示一般付款資訊
    const showGeneralPaymentInfo = function(methodKey) {
        const methodNames = {
            'credit': '信用卡',
            'webatm': '網路ATM',  
            'vacc': 'ATM轉帳',
            'cvs': '超商代碼',
            'barcode': '超商條碼',
            'SmartPay': '智慧ATM2.0'
        };
        
        const methodName = methodNames[methodKey] || methodKey;
        
        // 顯示模態或通知
        if (typeof alert !== 'undefined') {
            alert(`您選擇了 ${methodName} 付款方式。請前往購物車結帳以使用此付款方式。`);
        }
    };
    
    // 響應式功能
    const handleResponsive = function() {
        const checkScreenSize = function() {
            $('.wp-block-newebpay-payment-methods.is-layout-grid').each(function() {
                const $block = $(this);
                
                if ($(window).width() <= 768) {
                    $block.addClass('is-mobile');
                } else {
                    $block.removeClass('is-mobile');
                }
            });
        };
        
        // 初始檢查
        checkScreenSize();
        
        // 視窗大小變更時檢查
        $(window).on('resize', checkScreenSize);
    };
    
    // DOM 準備就緒時執行
    $(document).ready(function() {
        initPaymentMethodsBlocks();
        handlePaymentMethodChange();
        handleResponsive();
    });
    
})(jQuery);
