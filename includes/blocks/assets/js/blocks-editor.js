/**
 * Newebpay Blocks - Editor JavaScript
 * 
 * @package NewebpayPayment
 * @since 1.0.10
 */

(function() {
    'use strict';
    
    // 確保 WordPress 模組可用
    if ( typeof wp === 'undefined' ) {
        console.error( 'Newebpay Blocks: WordPress scripts not loaded' );
        return;
    }
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, CheckboxControl, SelectControl, ToggleControl } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment, useState, useEffect } = wp.element;
    
    // 確保 newebpayBlocks 資料可用
    const blockData = window.newebpayBlocks || {
        availableMethods: {},
        blocks: {}
    };
    
    // 付款方式選擇區塊
    registerBlockType('newebpay/payment-methods', {
        title: __('Newebpay 付款方式', 'newebpay-payment'),
        description: __('顯示 Newebpay 支援的付款方式選擇', 'newebpay-payment'),
        icon: {
            src: 'money-alt',
            background: '#0073aa'
        },
        category: 'newebpay',
        keywords: [
            __('payment', 'newebpay-payment'), 
            __('付款', 'newebpay-payment'), 
            __('newebpay', 'newebpay-payment'),
            __('金流', 'newebpay-payment')
        ],
        
        attributes: {
            showMethods: {
                type: 'array',
                default: []
            },
            layout: {
                type: 'string',
                default: 'grid'
            },
            showDescriptions: {
                type: 'boolean',
                default: true
            },
            columnsCount: {
                type: 'number',
                default: 3
            }
        },
        
        supports: {
            html: false,
            align: ['wide', 'full'],
            spacing: {
                margin: true,
                padding: true
            },
            color: {
                background: true,
                text: true
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes, clientId } = props;
            const { showMethods, layout, showDescriptions, columnsCount } = attributes;
            const blockProps = useBlockProps();
            
            // 取得可用的付款方式
            const availableMethods = blockData.availableMethods || {};
            const allMethods = Object.keys(availableMethods);
            
            // 狀態管理
            const [isLoading, setIsLoading] = useState(false);
            const [methodsData, setMethodsData] = useState(availableMethods);
            
            // 載入付款方式資料
            useEffect(() => {
                if (Object.keys(methodsData).length === 0 && blockData.apiUrl) {
                    setIsLoading(true);
                    fetch(blockData.apiUrl + 'payment-methods')
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data) {
                                setMethodsData(data.data);
                            }
                        })
                        .catch(error => {
                            console.error('Failed to load payment methods:', error);
                        })
                        .finally(() => {
                            setIsLoading(false);
                        });
                }
            }, []);
            
            // 建立付款方式選項
            const methodOptions = allMethods.map(function(methodKey) {
                const method = methodsData[methodKey] || availableMethods[methodKey];
                if (!method) return null;
                
                return {
                    label: method.name,
                    value: methodKey,
                    checked: showMethods.includes(methodKey)
                };
            }).filter(Boolean);
            
            // 處理付款方式變更
            const onMethodChange = function(methodKey, isChecked) {
                let newShowMethods;
                if (isChecked) {
                    newShowMethods = [...showMethods, methodKey];
                } else {
                    newShowMethods = showMethods.filter(method => method !== methodKey);
                }
                setAttributes({ showMethods: newShowMethods });
            };
            
            // 預覽內容
            const renderPreview = function() {
                if (isLoading) {
                    return el('div', { 
                        className: 'newebpay-block-loading' 
                    }, __('載入付款方式中...', 'newebpay-payment'));
                }
                
                const displayMethods = showMethods.length > 0 
                    ? showMethods.map(key => methodsData[key] || availableMethods[key]).filter(Boolean)
                    : Object.values(methodsData).length > 0 ? Object.values(methodsData) : Object.values(availableMethods);
                
                if (displayMethods.length === 0) {
                    return el('div', { 
                        className: 'newebpay-block-placeholder' 
                    }, [
                        el('h4', {}, __('Newebpay 付款方式', 'newebpay-payment')),
                        el('p', {}, __('請在右側設定中選擇要顯示的付款方式', 'newebpay-payment')),
                        el('p', { style: { fontSize: '0.9em', color: '#666' } }, 
                            __('如果沒有可選的付款方式，請先到 WooCommerce > 設定 > 付款 > Newebpay 中啟用付款方式。', 'newebpay-payment'))
                    ]);
                }
                
                const gridCols = layout === 'grid' ? Math.min(columnsCount, displayMethods.length) : 1;
                const gridStyle = layout === 'grid' ? {
                    display: 'grid',
                    gridTemplateColumns: `repeat(${gridCols}, 1fr)`,
                    gap: '1rem'
                } : {};
                
                return el('div', {
                    className: `wp-block-newebpay-payment-methods is-layout-${layout} is-editor-preview`,
                    style: gridStyle
                }, displayMethods.map(function(method, index) {
                    return el('div', {
                        key: index,
                        className: 'newebpay-payment-method preview-mode'
                    }, [
                        el('div', { className: 'method-icon' }, 
                            el('span', { className: `dashicons dashicons-${method.icon || 'money-alt'}` })
                        ),
                        el('div', { className: 'method-content' }, [
                            el('h4', { className: 'method-name' }, method.name),
                            showDescriptions && method.description ? 
                                el('p', { className: 'method-description' }, method.description) : null
                        ])
                    ]);
                }));
            };
            
            return el(Fragment, null, [
                // 側邊欄設定
                el(InspectorControls, null,
                    el(PanelBody, {
                        title: __('付款方式設定', 'newebpay-payment'),
                        initialOpen: true
                    }, [
                        // 佈局選擇
                        el(SelectControl, {
                            label: __('佈局樣式', 'newebpay-payment'),
                            value: layout,
                            options: [
                                { label: __('格子佈局', 'newebpay-payment'), value: 'grid' },
                                { label: __('列表佈局', 'newebpay-payment'), value: 'list' },
                                { label: __('內聯佈局', 'newebpay-payment'), value: 'inline' }
                            ],
                            onChange: function(value) {
                                setAttributes({ layout: value });
                            }
                        }),
                        
                        // 格子佈局的欄數設定
                        layout === 'grid' ? el(SelectControl, {
                            label: __('每行顯示數量', 'newebpay-payment'),
                            value: columnsCount,
                            options: [
                                { label: '1', value: 1 },
                                { label: '2', value: 2 },
                                { label: '3', value: 3 },
                                { label: '4', value: 4 }
                            ],
                            onChange: function(value) {
                                setAttributes({ columnsCount: parseInt(value) });
                            }
                        }) : null,
                        
                        // 顯示描述開關
                        el(ToggleControl, {
                            label: __('顯示付款方式描述', 'newebpay-payment'),
                            checked: showDescriptions,
                            onChange: function(value) {
                                setAttributes({ showDescriptions: value });
                            }
                        }),
                        
                        // 付款方式選擇
                        el('h4', { style: { marginTop: '20px', marginBottom: '10px' } }, 
                            __('選擇要顯示的付款方式', 'newebpay-payment')
                        ),
                        
                        methodOptions.length > 0 ? 
                            methodOptions.map(function(option) {
                                return el(CheckboxControl, {
                                    key: option.value,
                                    label: option.label,
                                    checked: option.checked,
                                    onChange: function(isChecked) {
                                        onMethodChange(option.value, isChecked);
                                    }
                                });
                            })
                        : el('p', { style: { color: '#666', fontStyle: 'italic' } }, 
                            __('沒有可用的付款方式。請先在 Newebpay 設定中啟用付款方式。', 'newebpay-payment'))
                    ])
                ),
                
                // 區塊預覽
                el('div', blockProps, renderPreview())
            ]);
        },
        
        save: function() {
            // 使用 PHP 渲染，所以這裡返回 null
            return null;
        }
    });
    
})();
