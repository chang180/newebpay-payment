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
            
            // 這裡可以添加與 WooCommerce 結帳流程的整合
            // 例如：更新隱藏的表單欄位、觸發 AJAX 請求等
        });
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
