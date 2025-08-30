/**
 * Newebpay Blocks - Editor JavaScript
 * 
 * @package NewebpayPayment
 * @since 1.0.10
 */

(function(wp) {
    'use strict';
    
    const { registerBlockType } = wp.blocks;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, CheckboxControl, SelectControl, ToggleControl } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;
    
    // 付款方式選擇區塊
    registerBlockType('newebpay/payment-methods', {
        title: __('Newebpay 付款方式', 'newebpay-payment'),
        description: __('顯示 Newebpay 支援的付款方式選擇', 'newebpay-payment'),
        icon: 'money-alt',
        category: 'newebpay',
        keywords: [__('payment', 'newebpay-payment'), __('付款', 'newebpay-payment'), __('newebpay', 'newebpay-payment')],
        
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
            }
        },
        
        supports: {
            html: false,
            align: ['wide', 'full'],
            spacing: {
                margin: true,
                padding: true
            }
        },
        
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { showMethods, layout, showDescriptions } = attributes;
            const blockProps = useBlockProps();
            
            // 取得可用的付款方式
            const availableMethods = newebpayBlocks.availableMethods || {};
            const allMethods = Object.keys(availableMethods);
            
            // 建立付款方式選項
            const methodOptions = allMethods.map(function(methodKey) {
                const method = availableMethods[methodKey];
                return {
                    label: method.name,
                    value: methodKey,
                    checked: showMethods.includes(methodKey)
                };
            });
            
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
                const displayMethods = showMethods.length > 0 
                    ? showMethods.map(key => availableMethods[key]).filter(Boolean)
                    : Object.values(availableMethods);
                
                if (displayMethods.length === 0) {
                    return el('p', { 
                        className: 'newebpay-block-placeholder' 
                    }, __('請在右側設定中選擇要顯示的付款方式', 'newebpay-payment'));
                }
                
                return el('div', {
                    className: `wp-block-newebpay-payment-methods is-layout-${layout} is-editor-preview`
                }, displayMethods.map(function(method, index) {
                    return el('div', {
                        key: index,
                        className: 'newebpay-payment-method'
                    }, [
                        el('div', { className: 'method-icon' }, 
                            el('span', { className: `dashicons dashicons-${method.icon}` })
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
    
})(window.wp);
