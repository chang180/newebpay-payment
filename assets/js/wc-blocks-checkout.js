/**
 * Newebpay WooCommerce Blocks 結帳整合
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 */

const { createElement, useState, useEffect } = window.wp.element;
const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const { __ } = window.wp.i18n;

// 取得付款方式設定
const settings = getSetting( 'newebpay_data', {} );
const defaultLabel = __( 'Newebpay', 'newebpay-payment' );

/**
 * Newebpay 付款方式標籤組件
 */
const Label = ( props ) => {
    const { PaymentMethodLabel } = props.components;
    
    return createElement( PaymentMethodLabel, {
        text: settings.title || defaultLabel,
        icon: settings.logo_url ? createElement( 'img', {
            src: settings.logo_url,
            alt: settings.title || defaultLabel,
            style: {
                width: '40px',
                height: 'auto',
                marginRight: '8px'
            }
        }) : null
    });
};

/**
 * Newebpay 付款方式內容組件
 */
const Content = () => {
    return createElement( 'div', {
        className: 'newebpay-payment-method-content'
    }, [
        settings.description && createElement( 'p', {
            key: 'description',
            className: 'newebpay-payment-description'
        }, settings.description ),
        
        createElement( 'div', {
            key: 'features',
            className: 'newebpay-payment-features'
        }, [
            createElement( 'p', {
                key: 'security',
                className: 'newebpay-security-notice'
            }, __( '✓ 安全加密傳輸', 'newebpay-payment' ) ),
            
            createElement( 'p', {
                key: 'methods',
                className: 'newebpay-methods-notice'  
            }, __( '✓ 支援信用卡、ATM、超商付款', 'newebpay-payment' ) )
        ])
    ]);
};

/**
 * Newebpay 付款方式編輯組件（在管理後台編輯區塊時顯示）
 */
const Edit = () => {
    return createElement( 'div', {
        className: 'newebpay-payment-method-edit'
    }, [
        createElement( 'h4', {
            key: 'title'
        }, __( 'Newebpay 付款設定', 'newebpay-payment' ) ),
        
        createElement( 'p', {
            key: 'info'
        }, __( '客戶將被重導向到藍新金流完成付款。', 'newebpay-payment' ) )
    ]);
};

/**
 * 付款方式配置
 */
const NewebpayPaymentMethod = {
    name: 'newebpay',
    label: createElement( Label ),
    content: createElement( Content ),
    edit: createElement( Edit ),
    canMakePayment: () => true,
    ariaLabel: settings.title || defaultLabel,
    supports: {
        features: settings.supports || [ 'products' ]
    }
};

// 註冊付款方式
registerPaymentMethod( NewebpayPaymentMethod );

// 加入自訂樣式
if ( typeof window !== 'undefined' && window.document ) {
    const style = document.createElement( 'style' );
    style.textContent = `
        .newebpay-payment-method-content {
            padding: 16px;
            background: #f8f9fa;
            border-radius: 4px;
            margin-top: 8px;
        }
        
        .newebpay-payment-description {
            margin: 0 0 12px 0;
            color: #555;
        }
        
        .newebpay-payment-features p {
            margin: 4px 0;
            font-size: 14px;
            color: #666;
        }
        
        .newebpay-security-notice,
        .newebpay-methods-notice {
            font-size: 13px;
            color: #008000;
        }
        
        .newebpay-payment-method-edit {
            padding: 16px;
            border: 1px dashed #ccc;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .newebpay-payment-method-edit h4 {
            margin: 0 0 8px 0;
            color: #333;
        }
        
        .newebpay-payment-method-edit p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        /* 付款方式標籤樣式 */
        .wc-block-components-payment-method-label img {
            vertical-align: middle;
        }
    `;
    document.head.appendChild( style );
}

console.log( 'Newebpay WooCommerce Blocks 付款方式已註冊' );
