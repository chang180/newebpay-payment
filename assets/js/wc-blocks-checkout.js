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
        text: settings.title || defaultLabel
    });
};

/**
 * Newebpay 付款方式內容組件
 */
const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;
    
    const [paymentMethods, setPaymentMethods] = useState([]);
    const [selectedMethod, setSelectedMethod] = useState('');
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState(null);
    const [showCVSCOMNotPayed, setShowCVSCOMNotPayed] = useState(true);
    const [cvscomNotPayed, setCvscomNotPayed] = useState(false);

    // 處理支付資料提交到 WooCommerce Blocks
    useEffect( () => {
        const unsubscribe = onPaymentSetup( () => {
            console.log('Newebpay: Payment setup triggered');
            console.log('Newebpay: Selected method:', selectedMethod);
            console.log('Newebpay: CVSCOM not payed:', cvscomNotPayed);
            
            // 準備要傳遞給後端的資料
            const paymentMethodData = {
                selectedMethod: selectedMethod,
                newebpay_selected_method: selectedMethod,
                cvscom_not_payed: cvscomNotPayed
            };
            
            console.log('Newebpay: Sending payment method data:', paymentMethodData);
            
            // 回傳成功狀態和資料給 WooCommerce Blocks
            if ( selectedMethod ) {
                return {
                    type: emitResponse.responseTypes.SUCCESS,
                    meta: {
                        paymentMethodData: paymentMethodData
                    }
                };
            } else {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: '請選擇付款方式'
                };
            }
        } );

        // 清理函式
        return () => {
            unsubscribe();
        };
    }, [ onPaymentSetup, emitResponse, selectedMethod, cvscomNotPayed ] );

    // 使用 window 物件來存儲資料，讓後端能夠存取 (保留作為備用)
    useEffect(() => {
        // 建立全域資料物件
        if (!window.newebpayData) {
            window.newebpayData = {};
        }
        
        // 更新全域資料
        window.newebpayData = {
            selectedMethod: selectedMethod,
            cvscomNotPayed: cvscomNotPayed
        };
        
        // 觸發自定義事件，通知表單資料已更新
        const dataEvent = new CustomEvent('newebpay-data-updated', {
            detail: window.newebpayData
        });
        window.dispatchEvent(dataEvent);
        
        console.log('Newebpay: 更新全域資料', window.newebpayData);
    }, [selectedMethod, cvscomNotPayed]);

    // 載入付款方式
    useEffect(() => {
        const fetchPaymentMethods = async () => {
            try {
                setIsLoading(true);
                const response = await fetch('/wp-json/newebpay/v1/payment-methods');
                const data = await response.json();
                
                if (data.success && data.data) {
                    setPaymentMethods(data.data);
                    // 預設選擇第一個付款方式
                    if (data.data.length > 0) {
                        setSelectedMethod(data.data[0].id);
                        // useEffect 會自動處理隱藏欄位更新
                    }
                    
                    // 檢查是否需要顯示超商取貨不付款選項
                    // 總是顯示超商取貨不付款選項，讓用戶可以選擇
                    setShowCVSCOMNotPayed(true);
                } else {
                    setError('無法載入付款方式');
                }
            } catch (err) {
                console.error('Failed to load payment methods:', err);
                setError('載入付款方式時發生錯誤');
            } finally {
                setIsLoading(false);
            }
        };

        fetchPaymentMethods();
    }, []);

    // 處理付款方式選擇
    const handleMethodChange = (event) => {
        const newMethod = event.target.value;
        setSelectedMethod(newMethod);
        
        // 如果選擇了超商取貨付款 (CVSCOM)，自動取消超商取貨不付款並隱藏選項
        let finalCvscomNotPayed = cvscomNotPayed;
        if (newMethod && (newMethod.toLowerCase() === 'cvscom' || newMethod.toLowerCase().includes('cvscom'))) {
            setCvscomNotPayed(false);
            setShowCVSCOMNotPayed(false); // 隱藏超商取貨不付款選項
            finalCvscomNotPayed = false;
        } else {
            setShowCVSCOMNotPayed(true); // 顯示超商取貨不付款選項
        }
        
        // useEffect 會自動處理隱藏欄位更新
        
        // 觸發自定義事件，讓其他組件知道選擇已改變
        const customEvent = new CustomEvent('newebpay-method-selected', {
            detail: { 
                method: newMethod,
                cvscomNotPayed: finalCvscomNotPayed
            }
        });
        window.dispatchEvent(customEvent);
    };

    // 處理超商取貨不付款選擇
    const handleCVSCOMChange = (event) => {
        const checked = event.target.checked;
        setCvscomNotPayed(checked);
        
        // 確保超商取貨不付款選項可見
        setShowCVSCOMNotPayed(true);
        
        // 如果選擇了超商取貨不付款，且當前付款方式是超商取貨付款 (CVSCOM)，則改選第一個非CVSCOM方式
        let finalMethod = selectedMethod;
        if (checked && selectedMethod && (selectedMethod.toLowerCase() === 'cvscom' || selectedMethod.toLowerCase().includes('cvscom'))) {
            const nonCVSCOMMethod = paymentMethods.find(method => 
                !(method.id.toLowerCase() === 'cvscom' || method.id.toLowerCase().includes('cvscom'))
            );
            if (nonCVSCOMMethod) {
                setSelectedMethod(nonCVSCOMMethod.id);
                finalMethod = nonCVSCOMMethod.id;
            }
        }
        
        // useEffect 會自動處理隱藏欄位更新
        
        // 觸發事件更新
        const customEvent = new CustomEvent('newebpay-method-selected', {
            detail: { 
                method: finalMethod,
                cvscomNotPayed: checked
            }
        });
        window.dispatchEvent(customEvent);
    };

    if (isLoading) {
        return createElement('div', {
            className: 'newebpay-payment-method-content'
        }, [
            createElement('p', {
                key: 'loading'
            }, '載入付款方式...')
        ]);
    }

    if (error) {
        return createElement('div', {
            className: 'newebpay-payment-method-content'
        }, [
            createElement('p', {
                key: 'error',
                style: { color: '#d63638' }
            }, error)
        ]);
    }

    return createElement('div', {
        className: 'newebpay-payment-method-content'
    }, [
        // 描述信息
        settings.description && createElement('p', {
            key: 'description',
            className: 'newebpay-payment-description'
        }, settings.description),

        // 付款方式選擇下拉選單
        paymentMethods.length > 0 && createElement('div', {
            key: 'method-selector',
            className: 'newebpay-method-selector'
        }, [
            createElement('label', {
                key: 'label',
                htmlFor: 'newebpay-method-select',
                className: 'newebpay-method-label'
            }, '付款方式：'),
            
            createElement('select', {
                key: 'select',
                id: 'newebpay-method-select',
                name: 'newebpay_selected_method',
                value: selectedMethod,
                onChange: handleMethodChange,
                className: 'newebpay-method-select'
            }, paymentMethods.map(method => {
                // 如果選擇了超商取貨不付款，禁用CVSCOM（超商取貨付款）
                const isDisabled = cvscomNotPayed && (method.id.toLowerCase() === 'cvscom' || method.id.toLowerCase().includes('cvscom'));
                
                return createElement('option', {
                    key: method.id,
                    value: method.id,
                    disabled: isDisabled,
                    style: isDisabled ? { color: '#ccc' } : {}
                }, method.name + (isDisabled ? ' (與超商取貨不付款互斥)' : ''))
            }))
        ]),

        // 超商取貨不付款選項
        showCVSCOMNotPayed && createElement('div', {
            key: 'cvscom-option',
            className: 'newebpay-cvscom-option'
        }, [
            createElement('input', {
                key: 'checkbox',
                type: 'checkbox',
                id: 'CVSCOMNotPayed',
                name: 'cvscom_not_payed',
                value: 'CVSCOMNotPayed',
                checked: cvscomNotPayed,
                onChange: handleCVSCOMChange,
                className: 'newebpay-cvscom-checkbox'
            }),
            createElement('label', {
                key: 'label',
                htmlFor: 'CVSCOMNotPayed',
                className: 'newebpay-cvscom-label'
            }, '超商取貨不付款')
        ]),

        // 選擇的付款方式詳細信息
        selectedMethod && paymentMethods.length > 0 && createElement('div', {
            key: 'method-details',
            className: 'newebpay-method-details'
        }, (() => {
            const currentMethod = paymentMethods.find(m => m.id === selectedMethod);
            return currentMethod ? [
                createElement('div', {
                    key: 'method-name',
                    className: 'newebpay-method-name'
                }, currentMethod.name),
                
                currentMethod.description && createElement('p', {
                    key: 'method-description',
                    className: 'newebpay-method-description'
                }, currentMethod.description)
            ] : [];
        })()),

        // 功能特色
        createElement('div', {
            key: 'features',
            className: 'newebpay-payment-features'
        }, [
            createElement('p', {
                key: 'security',
                className: 'newebpay-security-notice'
            }, __('✓ 安全加密傳輸', 'newebpay-payment')),
            
            createElement('p', {
                key: 'methods',
                className: 'newebpay-methods-notice'  
            }, __('✓ 支援信用卡、ATM、超商付款', 'newebpay-payment'))
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
    },
    // 添加付款方式選擇的處理
    paymentMethodId: 'newebpay',
    // 在表單提交時收集選擇的付款方式
    savedTokenComponent: null,
    // 支援自定義資料提交
    placeOrderButtonLabel: __( '前往付款', 'newebpay-payment' )
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
        
        /* 付款方式選擇器樣式 */
        .newebpay-method-selector {
            margin: 16px 0;
            padding: 12px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .newebpay-method-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .newebpay-method-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            background: #fff;
        }
        
        .newebpay-method-select:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
        }
        
        /* 超商取貨不付款選項樣式 */
        .newebpay-cvscom-option {
            margin: 12px 0;
            padding: 12px;
            background: #fff9c4;
            border: 1px solid #f0c36d;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        
        .newebpay-cvscom-checkbox {
            margin: 0 8px 0 0;
            width: 16px;
            height: 16px;
            accent-color: #f0c36d;
        }
        
        .newebpay-cvscom-label {
            margin: 0;
            font-weight: 500;
            color: #8a6d00;
            cursor: pointer;
            user-select: none;
        }
        
        .newebpay-cvscom-label:hover {
            color: #b8860b;
        }
        
        /* 付款方式詳細信息 */
        .newebpay-method-details {
            margin: 12px 0;
            padding: 12px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            display: flex;
            align-items: center;
        }
        
        .newebpay-method-icon {
            flex-shrink: 0;
        }
        
        .newebpay-method-description {
            margin: 0;
            color: #666;
            font-size: 13px;
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
        
        /* 響應式設計 */
        @media (max-width: 768px) {
            .newebpay-method-details {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .newebpay-method-icon {
                margin-bottom: 8px;
            }
        }
    `;
    document.head.appendChild( style );
}

console.log( 'Newebpay WooCommerce Blocks 付款方式已註冊' );

// 確保在結帳時正確提交付款方式選擇
document.addEventListener('DOMContentLoaded', function() {
    console.log('Newebpay: DOM 已載入，設置事件監聽器');
    
    // 監聽結帳按鈕點擊，確保資料已準備好
    document.addEventListener('click', function(event) {
        if (event.target.matches('.wc-block-components-checkout-place-order-button') ||
            event.target.closest('.wc-block-components-checkout-place-order-button')) {
            
            console.log('Newebpay: 結帳按鈕被點擊');
            
            // 從 sessionStorage 獲取最新選擇
            const selectedMethod = sessionStorage.getItem('newebpay_selected_method');
            const cvscomNotPayed = sessionStorage.getItem('newebpay_cvscom_not_payed') === 'true';
            
            console.log('Newebpay: 從 sessionStorage 獲取的值', { selectedMethod, cvscomNotPayed });
            
            // 確保隱藏欄位都已更新
            setTimeout(() => {
                // 直接設置到表單中
                const forms = [
                    document.querySelector('form.wc-block-checkout__form'),
                    document.querySelector('form[name="checkout"]'),
                    document.querySelector('.wc-block-checkout form'),
                    document.querySelector('form')
                ].filter(Boolean);
                
                forms.forEach(form => {
                    // 創建或更新傳統格式的隱藏欄位
                    let methodInput = form.querySelector('input[name="nwp_selected_payments"]');
                    if (!methodInput) {
                        methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = 'nwp_selected_payments';
                        form.appendChild(methodInput);
                    }
                    methodInput.value = selectedMethod || '';
                    
                    let cvscomInput = form.querySelector('input[name="cvscom_not_payed"]');
                    if (!cvscomInput) {
                        cvscomInput = document.createElement('input');
                        cvscomInput.type = 'hidden';
                        cvscomInput.name = 'cvscom_not_payed';
                        form.appendChild(cvscomInput);
                    }
                    cvscomInput.value = cvscomNotPayed ? 'CVSCOMNotPayed' : '';
                    
                    console.log('Newebpay: 在表單中設置隱藏欄位', {
                        form: form.className,
                        method: methodInput.value,
                        cvscom: cvscomInput.value
                    });
                });
            }, 100);
        }
    });
    
    // 監聽付款方式選擇變更，同步到 sessionStorage
    window.addEventListener('newebpay-method-selected', function(event) {
        console.log('Newebpay: 付款方式已選擇', event.detail);
        
        if (typeof Storage !== 'undefined') {
            sessionStorage.setItem('newebpay_selected_method', event.detail.method || '');
            sessionStorage.setItem('newebpay_cvscom_not_payed', event.detail.cvscomNotPayed ? 'true' : 'false');
        }
    });
    
    // 監聽全域資料更新，設置隱藏欄位
    window.addEventListener('newebpay-data-updated', function(event) {
        console.log('Newebpay: 全域資料已更新', event.detail);
        
        // 等待 DOM 準備好後設置隱藏欄位
        setTimeout(() => {
            const form = document.querySelector('form.wc-block-checkout__form') || 
                        document.querySelector('.wc-block-checkout__form') ||
                        document.querySelector('form[name="checkout"]') ||
                        document.querySelector('.wc-block-checkout') ||
                        document.body;
            
            console.log('Newebpay: 找到的表單容器', {
                formSelector: form.tagName + '.' + (form.className || 'no-class'),
                formId: form.id || 'no-id',
                isForm: form.tagName === 'FORM'
            });
            
            if (form) {
                // 設置 payment_method 隱藏欄位
                let paymentMethodInput = document.getElementById('newebpay_payment_method_hidden');
                if (!paymentMethodInput) {
                    paymentMethodInput = document.createElement('input');
                    paymentMethodInput.type = 'hidden';
                    paymentMethodInput.id = 'newebpay_payment_method_hidden';
                    paymentMethodInput.name = 'payment_method';
                    form.appendChild(paymentMethodInput);
                }
                paymentMethodInput.value = 'newebpay';
                
                // 設置付款方式隱藏欄位
                let methodInput = document.getElementById('newebpay_method_hidden');
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.id = 'newebpay_method_hidden';
                    methodInput.name = 'newebpay_selected_method';
                    form.appendChild(methodInput);
                }
                methodInput.value = event.detail.selectedMethod || '';
                
                // 設置兼容性隱藏欄位
                let compatInput = document.getElementById('nwp_payments_hidden');
                if (!compatInput) {
                    compatInput = document.createElement('input');
                    compatInput.type = 'hidden';
                    compatInput.id = 'nwp_payments_hidden';
                    compatInput.name = 'nwp_selected_payments';
                    form.appendChild(compatInput);
                }
                compatInput.value = event.detail.selectedMethod || '';
                
                // 設置超商取貨不付款隱藏欄位
                let cvscomInput = document.getElementById('newebpay_cvscom_hidden');
                if (!cvscomInput) {
                    cvscomInput = document.createElement('input');
                    cvscomInput.type = 'hidden';
                    cvscomInput.id = 'newebpay_cvscom_hidden';
                    cvscomInput.name = 'cvscom_not_payed';
                    form.appendChild(cvscomInput);
                }
                cvscomInput.value = event.detail.cvscomNotPayed ? 'CVSCOMNotPayed' : '';
                
                console.log('Newebpay: 已設置隱藏欄位', {
                    method: methodInput.value,
                    cvscom: cvscomInput.value
                });
            }
        }, 100);
    });
});
