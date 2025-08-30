/**
 * WordPress Block Editor for Newebpay Payment Methods
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 * @author Newebpay
 */

(function() {
    'use strict';
    
    // WordPress dependencies
    const { registerBlockType } = wp.blocks;
    const { createElement, useState, useEffect } = wp.element;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, SelectControl, ToggleControl, TextControl, Spinner } = wp.components;
    const { __ } = wp.i18n;
    const { apiFetch } = wp;

    /**
     * Block Registration
     */
    registerBlockType('newebpay/payment-methods', {
        title: __('Newebpay 付款方式', 'newebpay-payment'),
        description: __('顯示可用的付款方式選項', 'newebpay-payment'),
        icon: 'money-alt',
        category: 'woocommerce',
        keywords: [
            __('payment', 'newebpay-payment'),
            __('付款', 'newebpay-payment'),
            __('newebpay', 'newebpay-payment'),
            __('金流', 'newebpay-payment')
        ],
        
        attributes: {
            layout: {
                type: 'string',
                default: 'grid'
            },
            showIcons: {
                type: 'boolean',
                default: true
            },
            showDescriptions: {
                type: 'boolean',
                default: true
            },
            enableResponsive: {
                type: 'boolean',
                default: true
            },
            title: {
                type: 'string',
                default: ''
            },
            selectedMethods: {
                type: 'array',
                default: []
            },
            customClass: {
                type: 'string',
                default: ''
            }
        },

        /**
         * Block Editor Interface
         */
        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { 
                layout, 
                showIcons, 
                showDescriptions, 
                enableResponsive, 
                title, 
                selectedMethods,
                customClass 
            } = attributes;

            // State management
            const [paymentMethods, setPaymentMethods] = useState([]);
            const [isLoading, setIsLoading] = useState(true);
            const [error, setError] = useState(null);
            const [apiStatus, setApiStatus] = useState('checking');

            /**
             * Fetch payment methods from API
             */
            useEffect(() => {
                const fetchPaymentMethods = async () => {
                    try {
                        setIsLoading(true);
                        setError(null);
                        
                        // Check API status first
                        const statusResponse = await apiFetch({
                            path: '/newebpay/v1/status',
                            method: 'GET'
                        });
                        
                        setApiStatus(statusResponse.status || 'unknown');
                        
                        // Fetch payment methods
                        const methodsResponse = await apiFetch({
                            path: '/newebpay/v1/payment-methods',
                            method: 'GET'
                        });
                        
                        if (methodsResponse && methodsResponse.success) {
                            setPaymentMethods(methodsResponse.data || []);
                        } else {
                            throw new Error(methodsResponse.message || 'Failed to fetch payment methods');
                        }
                        
                    } catch (err) {
                        console.error('Newebpay Block Error:', err);
                        setError(err.message || 'Unknown error occurred');
                        setPaymentMethods([]);
                    } finally {
                        setIsLoading(false);
                    }
                };

                fetchPaymentMethods();
            }, []);

            /**
             * Handle method selection change
             */
            const handleMethodToggle = (methodId) => {
                const updatedMethods = selectedMethods.includes(methodId)
                    ? selectedMethods.filter(id => id !== methodId)
                    : [...selectedMethods, methodId];
                    
                setAttributes({ selectedMethods: updatedMethods });
            };

            /**
             * Get filtered payment methods
             */
            const getFilteredMethods = () => {
                if (selectedMethods.length === 0) {
                    return paymentMethods;
                }
                return paymentMethods.filter(method => 
                    selectedMethods.includes(method.id)
                );
            };

            /**
             * Render payment method preview
             */
            const renderPaymentMethod = (method) => {
                const baseClass = 'newebpay-method';
                const layoutClass = `${baseClass}--${layout}`;
                
                return createElement('div', {
                    key: method.id,
                    className: `${baseClass} ${layoutClass}`,
                    style: {
                        padding: '12px',
                        border: '1px solid #ddd',
                        borderRadius: '4px',
                        marginBottom: layout === 'list' ? '8px' : '0',
                        backgroundColor: '#f9f9f9'
                    }
                }, [
                    showIcons && method.icon && createElement('div', {
                        key: 'icon',
                        className: `${baseClass}__icon`,
                        style: { marginBottom: '8px' }
                    }, [
                        createElement('img', {
                            src: method.icon,
                            alt: method.title,
                            style: { maxWidth: '40px', height: 'auto' }
                        })
                    ]),
                    
                    createElement('div', {
                        key: 'title',
                        className: `${baseClass}__title`,
                        style: { 
                            fontWeight: 'bold', 
                            marginBottom: showDescriptions ? '4px' : '0' 
                        }
                    }, method.title),
                    
                    showDescriptions && method.description && createElement('div', {
                        key: 'description',
                        className: `${baseClass}__description`,
                        style: { 
                            fontSize: '0.9em', 
                            color: '#666' 
                        }
                    }, method.description)
                ]);
            };

            /**
             * Render block preview
             */
            const renderPreview = () => {
                if (isLoading) {
                    return createElement('div', {
                        style: { 
                            textAlign: 'center', 
                            padding: '40px',
                            backgroundColor: '#f0f0f0',
                            borderRadius: '4px'
                        }
                    }, [
                        createElement(Spinner),
                        createElement('p', { 
                            style: { marginTop: '10px' } 
                        }, __('載入付款方式...', 'newebpay-payment'))
                    ]);
                }

                if (error) {
                    return createElement('div', {
                        style: { 
                            padding: '20px',
                            backgroundColor: '#fff2cd',
                            border: '1px solid #ffeaa7',
                            borderRadius: '4px',
                            color: '#856404'
                        }
                    }, [
                        createElement('strong', {}, __('錯誤:', 'newebpay-payment')),
                        createElement('br'),
                        error,
                        createElement('br'),
                        createElement('small', {}, 
                            __('API 狀態: ', 'newebpay-payment') + apiStatus
                        )
                    ]);
                }

                const filteredMethods = getFilteredMethods();
                
                if (filteredMethods.length === 0) {
                    return createElement('div', {
                        style: { 
                            textAlign: 'center', 
                            padding: '40px',
                            backgroundColor: '#f8f9fa',
                            border: '2px dashed #dee2e6',
                            borderRadius: '4px',
                            color: '#6c757d'
                        }
                    }, [
                        createElement('p', {}, __('暫無可用的付款方式', 'newebpay-payment')),
                        createElement('small', {}, 
                            __('請在 WooCommerce 設定中啟用付款方式', 'newebpay-payment')
                        )
                    ]);
                }

                return createElement('div', {
                    className: `newebpay-blocks-container ${customClass}`.trim(),
                    style: {
                        display: layout === 'inline' ? 'flex' : 'grid',
                        gridTemplateColumns: layout === 'grid' ? 'repeat(auto-fit, minmax(200px, 1fr))' : '1fr',
                        gap: '16px',
                        padding: '16px',
                        backgroundColor: '#fff',
                        border: '1px solid #e0e0e0',
                        borderRadius: '8px'
                    }
                }, [
                    title && createElement('h3', {
                        key: 'title',
                        style: { 
                            gridColumn: '1 / -1',
                            margin: '0 0 16px 0',
                            fontSize: '1.2em',
                            color: '#333'
                        }
                    }, title),
                    ...filteredMethods.map(renderPaymentMethod)
                ]);
            };

            /**
             * Render inspector controls
             */
            const renderInspectorControls = () => {
                return createElement(InspectorControls, {}, [
                    // 基本設定
                    createElement(PanelBody, {
                        key: 'basic-settings',
                        title: __('基本設定', 'newebpay-payment'),
                        initialOpen: true
                    }, [
                        createElement(TextControl, {
                            key: 'title-control',
                            label: __('標題', 'newebpay-payment'),
                            value: title,
                            onChange: (value) => setAttributes({ title: value }),
                            placeholder: __('輸入區塊標題（選填）', 'newebpay-payment')
                        }),
                        
                        createElement(SelectControl, {
                            key: 'layout-control',
                            label: __('佈局樣式', 'newebpay-payment'),
                            value: layout,
                            options: [
                                { label: __('網格佈局', 'newebpay-payment'), value: 'grid' },
                                { label: __('列表佈局', 'newebpay-payment'), value: 'list' },
                                { label: __('行內佈局', 'newebpay-payment'), value: 'inline' }
                            ],
                            onChange: (value) => setAttributes({ layout: value })
                        }),
                        
                        createElement(TextControl, {
                            key: 'class-control',
                            label: __('自訂 CSS 類別', 'newebpay-payment'),
                            value: customClass,
                            onChange: (value) => setAttributes({ customClass: value }),
                            placeholder: __('my-custom-class', 'newebpay-payment')
                        })
                    ]),
                    
                    // 顯示選項
                    createElement(PanelBody, {
                        key: 'display-settings',
                        title: __('顯示選項', 'newebpay-payment'),
                        initialOpen: false
                    }, [
                        createElement(ToggleControl, {
                            key: 'icons-toggle',
                            label: __('顯示圖示', 'newebpay-payment'),
                            checked: showIcons,
                            onChange: (value) => setAttributes({ showIcons: value })
                        }),
                        
                        createElement(ToggleControl, {
                            key: 'descriptions-toggle',
                            label: __('顯示描述', 'newebpay-payment'),
                            checked: showDescriptions,
                            onChange: (value) => setAttributes({ showDescriptions: value })
                        }),
                        
                        createElement(ToggleControl, {
                            key: 'responsive-toggle',
                            label: __('響應式設計', 'newebpay-payment'),
                            checked: enableResponsive,
                            onChange: (value) => setAttributes({ enableResponsive: value })
                        })
                    ]),
                    
                    // 付款方式選擇
                    paymentMethods.length > 0 && createElement(PanelBody, {
                        key: 'methods-settings',
                        title: __('付款方式選擇', 'newebpay-payment'),
                        initialOpen: false
                    }, [
                        createElement('p', {
                            key: 'methods-help',
                            style: { fontSize: '0.9em', color: '#666', marginBottom: '10px' }
                        }, __('選擇要顯示的付款方式（空白表示顯示全部）', 'newebpay-payment')),
                        
                        ...paymentMethods.map(method => 
                            createElement(ToggleControl, {
                                key: `method-${method.id}`,
                                label: method.title,
                                checked: selectedMethods.length === 0 || selectedMethods.includes(method.id),
                                onChange: () => handleMethodToggle(method.id)
                            })
                        )
                    ]),
                    
                    // 狀態資訊
                    createElement(PanelBody, {
                        key: 'status-info',
                        title: __('狀態資訊', 'newebpay-payment'),
                        initialOpen: false
                    }, [
                        createElement('div', {
                            key: 'status-content',
                            style: { fontSize: '0.9em' }
                        }, [
                            createElement('p', {}, [
                                createElement('strong', {}, __('API 狀態: ', 'newebpay-payment')),
                                createElement('span', { 
                                    style: { 
                                        color: apiStatus === 'active' ? '#008000' : '#ff6b6b' 
                                    } 
                                }, apiStatus)
                            ]),
                            createElement('p', {}, [
                                createElement('strong', {}, __('可用付款方式: ', 'newebpay-payment')),
                                paymentMethods.length
                            ]),
                            createElement('p', {}, [
                                createElement('strong', {}, __('選擇的方式: ', 'newebpay-payment')),
                                selectedMethods.length === 0 ? __('全部', 'newebpay-payment') : selectedMethods.length
                            ])
                        ])
                    ])
                ]);
            };

            // Main render
            return createElement('div', {}, [
                renderInspectorControls(),
                renderPreview()
            ]);
        },

        /**
         * Save function - output for frontend
         */
        save: function(props) {
            const { attributes } = props;
            
            // Return null to use PHP render callback
            return null;
        }
    });

    /**
     * Additional block variations
     */
    wp.blocks.registerBlockVariation('newebpay/payment-methods', {
        name: 'newebpay-payment-grid',
        title: __('Newebpay 付款方式 (網格)', 'newebpay-payment'),
        description: __('以網格方式顯示付款選項', 'newebpay-payment'),
        attributes: {
            layout: 'grid',
            showIcons: true,
            showDescriptions: true
        },
        isDefault: true
    });

    wp.blocks.registerBlockVariation('newebpay/payment-methods', {
        name: 'newebpay-payment-list',
        title: __('Newebpay 付款方式 (列表)', 'newebpay-payment'),
        description: __('以列表方式顯示付款選項', 'newebpay-payment'),
        attributes: {
            layout: 'list',
            showIcons: true,
            showDescriptions: true
        }
    });

    wp.blocks.registerBlockVariation('newebpay/payment-methods', {
        name: 'newebpay-payment-inline',
        title: __('Newebpay 付款方式 (水平)', 'newebpay-payment'),
        description: __('以水平排列顯示付款選項', 'newebpay-payment'),
        attributes: {
            layout: 'inline',
            showIcons: true,
            showDescriptions: false
        }
    });

})();
