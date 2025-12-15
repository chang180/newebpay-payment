/**
 * Newebpay WooCommerce Blocks 結帳整合
 * 
 * @package NeWebPay_Payment
 * @version 1.1.0
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
    const [instPeriods, setInstPeriods] = useState([]);
    const [selectedInstPeriod, setSelectedInstPeriod] = useState('');
    const [showInstPeriod, setShowInstPeriod] = useState(false);

    // 處理支付資料提交到 WooCommerce Blocks
    useEffect( () => {
        const unsubscribe = onPaymentSetup( () => {
            // 準備要傳遞給後端的資料
            // 使用 backend id（從 UI id 轉換）來作為提交值
            const backendSelected = uiToBackendId(selectedMethod);
            const paymentMethodData = {
                selectedMethod: backendSelected,
                newebpay_selected_method: backendSelected,
                cvscom_not_payed: cvscomNotPayed,
                nwp_inst_period: selectedInstPeriod
            };
            
            // 驗證：如果選擇了信用卡分期且商店設定了期數，必須選擇期數
            // 如果商店沒有設定期數，則不需要選擇（會使用預設值 InstFlag = 1）
            const currentMethod = paymentMethods.find(m => m.ui_id === selectedMethod || m.id === selectedMethod);
            const isInstMethod = currentMethod && (currentMethod.id === 'Inst' || currentMethod.frontend_id === 'installment');
            
            if (isInstMethod && instPeriods.length > 0 && !selectedInstPeriod) {
                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: __('請選擇分期期數', 'newebpay-payment')
                };
            }
            
            // 如果商店沒有設定期數，不需要驗證，允許提交（後端會使用 InstFlag = 1）
            
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
    }, [ onPaymentSetup, emitResponse, selectedMethod, cvscomNotPayed, selectedInstPeriod ] );

    // 使用 window 物件來存儲資料，讓後端能夠存取 (保留作為備用)
    useEffect(() => {
        // 建立全域資料物件
        if (!window.newebpayData) {
            window.newebpayData = {};
        }
        
        // 更新全域資料
        window.newebpayData = {
            selectedMethod: selectedMethod,
            cvscomNotPayed: cvscomNotPayed,
            nwp_inst_period: selectedInstPeriod
        };
        
        // 觸發自定義事件，通知表單資料已更新
        const dataEvent = new CustomEvent('newebpay-data-updated', {
            detail: window.newebpayData
        });
        window.dispatchEvent(dataEvent);
        
    }, [selectedMethod, cvscomNotPayed, selectedInstPeriod]);

    // 載入付款方式
    useEffect(() => {
        const fetchPaymentMethods = async () => {
            try {
                setIsLoading(true);
                const apiUrl = window.newebpayBlocksData?.apiUrl || '/wp-json/newebpay/v1';
                console.log('Newebpay: Fetching payment methods from:', `${apiUrl}/payment-methods`);
                
                const response = await fetch(`${apiUrl}/payment-methods`);
                console.log('Newebpay: API response status:', response.status);
                
                const data = await response.json();
                console.log('Newebpay: API response data:', data);
                
                if (data.success && data.data) {
                    console.log('Newebpay: Setting payment methods:', data.data);

                    // 優先使用 API 回傳的 frontend_id（如果存在）來做為 UI 的 value
                    const uiMethods = data.data.map(method => {
                        return Object.assign({}, method, {
                            ui_id: method.frontend_id ? method.frontend_id : method.id
                        });
                    });

                    setPaymentMethods(uiMethods);
                    // 預設選擇第一個付款方式（使用 ui_id）
                    if (uiMethods.length > 0) {
                        const firstMethod = uiMethods[0];
                        setSelectedMethod(firstMethod.ui_id);
                        
                        // 如果第一個付款方式是信用卡分期，且商店設定了期數，則顯示期數選擇
                        if ((firstMethod.id === 'Inst' || firstMethod.frontend_id === 'installment') && 
                            data.inst_periods && Array.isArray(data.inst_periods) && data.inst_periods.length > 0) {
                            setShowInstPeriod(true);
                            // 預設選擇第一個期數
                            setSelectedInstPeriod(data.inst_periods[0].toString());
                        } else {
                            setShowInstPeriod(false);
                        }
                    }
                    
                    // 檢查是否需要顯示超商取貨不付款選項
                    // 總是顯示超商取貨不付款選項，讓用戶可以選擇
                    setShowCVSCOMNotPayed(true);
                    
                    // 設定信用卡分期期數
                    // 只有在商店設定了期數時才設定，否則留空（後端會使用 InstFlag = 1）
                    if (data.inst_periods && Array.isArray(data.inst_periods) && data.inst_periods.length > 0) {
                        setInstPeriods(data.inst_periods);
                        // 預設選擇第一個期數（只有在顯示期數選擇器時才需要）
                        // 這裡先設定，如果第一個付款方式不是分期，會在 handleMethodChange 中清除
                    } else {
                        setInstPeriods([]);
                        setSelectedInstPeriod('');
                    }
                } else {
                    console.error('Newebpay: API returned unsuccessful response:', data);
                    setError('無法載入付款方式');
                }
            } catch (err) {
                console.error('Newebpay: Error fetching payment methods:', err);
                setError('載入付款方式時發生錯誤');
            } finally {
                setIsLoading(false);
            }
        };

        fetchPaymentMethods();
    }, []);

    // 處理付款方式選擇
    // helper: 把 UI id 轉回 backend id（用於提交）
    const uiToBackendId = (uiId) => {
        const found = paymentMethods.find(m => m.ui_id === uiId || m.id === uiId);
        return found ? (found.id || found.ui_id) : uiId;
    };

    const handleMethodChange = (event) => {
        const newMethod = event.target.value;
        setSelectedMethod(newMethod);
        
        // 如果選擇了超商取貨付款 (CVSCOM)，自動取消超商取貨不付款並隱藏選項
        let finalCvscomNotPayed = cvscomNotPayed;
        // 判斷時使用 uiToBackendId 來比對後端鍵
        const backendId = uiToBackendId(newMethod).toLowerCase();
        if (backendId && (backendId === 'cvscom' || backendId.includes('cvscom'))) {
            setCvscomNotPayed(false);
            setShowCVSCOMNotPayed(false); // 隱藏超商取貨不付款選項
            finalCvscomNotPayed = false;
        } else {
            setShowCVSCOMNotPayed(true); // 顯示超商取貨不付款選項
        }
        
        // 檢查是否選擇了信用卡分期付款
        // 只有在商店設定了期數時才顯示期數選擇器
        const currentMethod = paymentMethods.find(m => m.ui_id === newMethod || m.id === newMethod);
        const isInstMethod = currentMethod && (currentMethod.id === 'Inst' || currentMethod.frontend_id === 'installment' || backendId === 'inst' || backendId === 'installment');
        
        if (isInstMethod && instPeriods.length > 0) {
            // 商店設定了期數，顯示分期期數選擇
            setShowInstPeriod(true);
            // 如果還沒有選擇期數，預設選擇第一個
            if (!selectedInstPeriod) {
                setSelectedInstPeriod(instPeriods[0].toString());
            }
        } else {
            // 隱藏分期期數選擇（商店沒有設定期數，或選擇了其他付款方式）
            setShowInstPeriod(false);
            setSelectedInstPeriod('');
        }
        
        // useEffect 會自動處理隱藏欄位更新
        
        // 觸發自定義事件，讓其他組件知道選擇已改變
        const customEvent = new CustomEvent('newebpay-method-selected', {
            detail: { 
                method: newMethod,
                cvscomNotPayed: finalCvscomNotPayed,
                instPeriod: selectedInstPeriod
            }
        });
        window.dispatchEvent(customEvent);
    };
    
    // 處理分期期數選擇
    const handleInstPeriodChange = (event) => {
        const newPeriod = event.target.value;
        setSelectedInstPeriod(newPeriod);
        
        // 觸發自定義事件
        const customEvent = new CustomEvent('newebpay-method-selected', {
            detail: { 
                method: selectedMethod,
                cvscomNotPayed: cvscomNotPayed,
                instPeriod: newPeriod
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

        // 信用卡分期期數選擇（只有在商店設定了期數時才顯示）
        showInstPeriod && instPeriods.length > 0 && createElement('div', {
            key: 'inst-period-selector',
            className: 'newebpay-inst-period-selector'
        }, [
            createElement('label', {
                key: 'label',
                htmlFor: 'nwp_inst_period',
                className: 'newebpay-inst-period-label'
            }, __('分期期數', 'newebpay-payment') + '：'),
            createElement('select', {
                key: 'select',
                id: 'nwp_inst_period',
                name: 'nwp_inst_period',
                value: selectedInstPeriod,
                onChange: handleInstPeriodChange,
                className: 'newebpay-inst-period-select',
                required: true
            }, instPeriods.map(period => {
                return createElement('option', {
                    key: period,
                    value: period.toString()
                }, period + __('期', 'newebpay-payment'))
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
            const currentMethod = paymentMethods.find(m => m.ui_id === selectedMethod || m.id === selectedMethod);
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
        
        /* 信用卡分期期數選擇器樣式 */
        .newebpay-inst-period-selector {
            margin: 12px 0;
            padding: 12px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .newebpay-inst-period-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .newebpay-inst-period-select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            background: #fff;
        }
        
        .newebpay-inst-period-select:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
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

// 確保在結帳時正確提交付款方式選擇
document.addEventListener('DOMContentLoaded', function() {
    // 監聽結帳按鈕點擊，確保資料已準備好
    document.addEventListener('click', function(event) {
        if (event.target.matches('.wc-block-components-checkout-place-order-button') ||
            event.target.closest('.wc-block-components-checkout-place-order-button')) {
            
                // 從 sessionStorage 獲取最新選擇（這裡存的是 backend id）
            const selectedMethod = sessionStorage.getItem('newebpay_selected_method');
            const cvscomNotPayed = sessionStorage.getItem('newebpay_cvscom_not_payed') === 'true';
            
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
                    
                });
            }, 100);
        }
    });
    
    // 監聽付款方式選擇變更，同步到 sessionStorage
    window.addEventListener('newebpay-method-selected', function(event) {
        if (typeof Storage !== 'undefined') {
            sessionStorage.setItem('newebpay_selected_method', event.detail.method || '');
            sessionStorage.setItem('newebpay_cvscom_not_payed', event.detail.cvscomNotPayed ? 'true' : 'false');
            sessionStorage.setItem('newebpay_inst_period', event.detail.instPeriod || '');
        }
    });
    
    // 監聽全域資料更新，設置隱藏欄位
    window.addEventListener('newebpay-data-updated', function(event) {
        
        // 等待 DOM 準備好後設置隱藏欄位
        setTimeout(() => {
            const form = document.querySelector('form.wc-block-checkout__form') || 
                        document.querySelector('.wc-block-checkout__form') ||
                        document.querySelector('form[name="checkout"]') ||
                        document.querySelector('.wc-block-checkout') ||
                        document.body;
            
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
                
            }
        }, 100);
    });
});
