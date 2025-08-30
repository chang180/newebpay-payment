/**
 * Frontend JavaScript for Newebpay Payment Blocks
 * 
 * @package NeWebPay_Payment
 * @version 1.0.10
 * @author Newebpay
 */

(function() {
    'use strict';
    
    /**
     * Main Newebpay Blocks Frontend Controller
     */
    class NewebpayBlocksFrontend {
        constructor() {
            this.blocks = [];
            this.isWooCommerce = typeof window.woocommerce !== 'undefined';
            this.debugMode = window.newebpayBlocks?.debug || false;
            
            this.init();
        }

        /**
         * Initialize frontend functionality
         */
        init() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.onDOMReady());
            } else {
                this.onDOMReady();
            }
        }

        /**
         * Handle DOM ready event
         */
        onDOMReady() {
            this.findBlocks();
            this.initializeBlocks();
            this.bindEvents();
            this.handleResponsive();
            
            if (this.debugMode) {
                console.log('Newebpay Blocks Frontend initialized', {
                    blocksFound: this.blocks.length,
                    wooCommerce: this.isWooCommerce
                });
            }
        }

        /**
         * Find all Newebpay blocks on the page
         */
        findBlocks() {
            const blockContainers = document.querySelectorAll('.newebpay-blocks-container');
            
            blockContainers.forEach((container, index) => {
                const blockData = {
                    id: `newebpay-block-${index}`,
                    container: container,
                    methods: container.querySelectorAll('.newebpay-method'),
                    layout: this.getBlockLayout(container),
                    config: this.getBlockConfig(container)
                };
                
                this.blocks.push(blockData);
            });
        }

        /**
         * Get block layout from container class
         */
        getBlockLayout(container) {
            if (container.classList.contains('newebpay-blocks--list')) return 'list';
            if (container.classList.contains('newebpay-blocks--inline')) return 'inline';
            return 'grid';
        }

        /**
         * Get block configuration from data attributes
         */
        getBlockConfig(container) {
            return {
                enableSelection: container.dataset.enableSelection !== 'false',
                allowMultiple: container.dataset.allowMultiple === 'true',
                enableTooltips: container.dataset.enableTooltips === 'true',
                enableAnalytics: container.dataset.enableAnalytics === 'true'
            };
        }

        /**
         * Initialize all blocks
         */
        initializeBlocks() {
            this.blocks.forEach(block => this.initializeBlock(block));
        }

        /**
         * Initialize a single block
         */
        initializeBlock(block) {
            // Add loading state
            block.container.classList.add('newebpay-blocks--loading');
            
            // Initialize methods
            block.methods.forEach(method => this.initializeMethod(method, block));
            
            // Remove loading state
            setTimeout(() => {
                block.container.classList.remove('newebpay-blocks--loading');
                block.container.classList.add('newebpay-blocks--ready');
            }, 100);

            // Initialize tooltips if enabled
            if (block.config.enableTooltips) {
                this.initializeTooltips(block);
            }

            // Track analytics if enabled
            if (block.config.enableAnalytics) {
                this.trackBlockView(block);
            }
        }

        /**
         * Initialize a single payment method
         */
        initializeMethod(method, block) {
            const methodId = method.dataset.methodId;
            const methodType = method.dataset.methodType;
            
            // Add interactive states
            method.setAttribute('tabindex', '0');
            method.setAttribute('role', 'button');
            method.setAttribute('aria-label', method.querySelector('.newebpay-method__title')?.textContent || '');
            
            // Handle method selection
            if (block.config.enableSelection) {
                method.addEventListener('click', (e) => this.handleMethodClick(e, method, block));
                method.addEventListener('keydown', (e) => this.handleMethodKeydown(e, method, block));
            }

            // Add hover effects
            method.addEventListener('mouseenter', () => this.handleMethodHover(method, true));
            method.addEventListener('mouseleave', () => this.handleMethodHover(method, false));

            // Special handling for specific payment types
            if (methodType) {
                this.initializeSpecialMethod(method, methodType, block);
            }
        }

        /**
         * Handle method click
         */
        handleMethodClick(event, method, block) {
            event.preventDefault();
            
            const methodId = method.dataset.methodId;
            const wasSelected = method.classList.contains('newebpay-method--selected');
            
            // Handle selection logic
            if (!block.config.allowMultiple) {
                // Single selection - clear others
                block.methods.forEach(m => m.classList.remove('newebpay-method--selected'));
            }
            
            if (wasSelected && block.config.allowMultiple) {
                // Deselect if multiple allowed
                method.classList.remove('newebpay-method--selected');
            } else {
                // Select method
                method.classList.add('newebpay-method--selected');
            }

            // Trigger events
            this.triggerMethodSelection(method, block, !wasSelected);
            
            // WooCommerce integration
            if (this.isWooCommerce) {
                this.handleWooCommerceSelection(method, block);
            }

            // Analytics tracking
            if (block.config.enableAnalytics) {
                this.trackMethodSelection(method, block);
            }
        }

        /**
         * Handle keyboard navigation
         */
        handleMethodKeydown(event, method, block) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                this.handleMethodClick(event, method, block);
            }
        }

        /**
         * Handle method hover effects
         */
        handleMethodHover(method, isHovering) {
            if (isHovering) {
                method.classList.add('newebpay-method--hover');
            } else {
                method.classList.remove('newebpay-method--hover');
            }
        }

        /**
         * Initialize special payment methods (ATM, Credit Card, etc.)
         */
        initializeSpecialMethod(method, methodType, block) {
            switch (methodType) {
                case 'CREDIT':
                    this.initializeCreditCardMethod(method, block);
                    break;
                case 'VACC':
                    this.initializeATMMethod(method, block);
                    break;
                case 'CVS':
                    this.initializeCVSMethod(method, block);
                    break;
                case 'BARCODE':
                    this.initializeBarcodeMethod(method, block);
                    break;
                default:
                    break;
            }
        }

        /**
         * Initialize credit card specific features
         */
        initializeCreditCardMethod(method, block) {
            // Add credit card specific functionality
            const cardInfo = method.querySelector('.newebpay-method__card-info');
            if (cardInfo) {
                // Enhance credit card display
                this.enhanceCreditCardDisplay(cardInfo);
            }
        }

        /**
         * Initialize ATM specific features
         */
        initializeATMMethod(method, block) {
            // Add ATM specific functionality
            const atmInfo = method.querySelector('.newebpay-method__atm-info');
            if (atmInfo) {
                // Add ATM bank information
                this.enhanceATMDisplay(atmInfo);
            }
        }

        /**
         * Initialize CVS specific features
         */
        initializeCVSMethod(method, block) {
            // Add convenience store specific functionality
            const cvsInfo = method.querySelector('.newebpay-method__cvs-info');
            if (cvsInfo) {
                this.enhanceCVSDisplay(cvsInfo);
            }
        }

        /**
         * Initialize barcode specific features
         */
        initializeBarcodeMethod(method, block) {
            // Add barcode specific functionality
            const barcodeInfo = method.querySelector('.newebpay-method__barcode-info');
            if (barcodeInfo) {
                this.enhanceBarcodeDisplay(barcodeInfo);
            }
        }

        /**
         * Initialize tooltips for block
         */
        initializeTooltips(block) {
            const methodsWithTooltips = block.container.querySelectorAll('[data-tooltip]');
            
            methodsWithTooltips.forEach(element => {
                const tooltip = this.createTooltip(element.dataset.tooltip);
                this.attachTooltip(element, tooltip);
            });
        }

        /**
         * Create tooltip element
         */
        createTooltip(text) {
            const tooltip = document.createElement('div');
            tooltip.className = 'newebpay-tooltip';
            tooltip.textContent = text;
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 14px;
                z-index: 1000;
                pointer-events: none;
                opacity: 0;
                transition: opacity 0.3s;
            `;
            return tooltip;
        }

        /**
         * Attach tooltip to element
         */
        attachTooltip(element, tooltip) {
            element.addEventListener('mouseenter', () => {
                document.body.appendChild(tooltip);
                this.positionTooltip(element, tooltip);
                tooltip.style.opacity = '1';
            });

            element.addEventListener('mouseleave', () => {
                tooltip.style.opacity = '0';
                setTimeout(() => {
                    if (tooltip.parentNode) {
                        tooltip.parentNode.removeChild(tooltip);
                    }
                }, 300);
            });
        }

        /**
         * Position tooltip relative to element
         */
        positionTooltip(element, tooltip) {
            const rect = element.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            tooltip.style.left = (rect.left + rect.width / 2 - tooltipRect.width / 2) + 'px';
            tooltip.style.top = (rect.top - tooltipRect.height - 8) + 'px';
        }

        /**
         * Trigger method selection event
         */
        triggerMethodSelection(method, block, isSelected) {
            const event = new CustomEvent('newebpayMethodSelection', {
                detail: {
                    method: method,
                    methodId: method.dataset.methodId,
                    methodType: method.dataset.methodType,
                    block: block,
                    isSelected: isSelected,
                    selectedMethods: this.getSelectedMethods(block)
                }
            });
            
            document.dispatchEvent(event);
        }

        /**
         * Get selected methods for a block
         */
        getSelectedMethods(block) {
            const selected = [];
            block.methods.forEach(method => {
                if (method.classList.contains('newebpay-method--selected')) {
                    selected.push({
                        id: method.dataset.methodId,
                        type: method.dataset.methodType,
                        element: method
                    });
                }
            });
            return selected;
        }

        /**
         * Handle WooCommerce integration
         */
        handleWooCommerceSelection(method, block) {
            if (!this.isWooCommerce) return;
            
            const methodId = method.dataset.methodId;
            const wooPaymentMethod = document.querySelector(`input[name="payment_method"][value="${methodId}"]`);
            
            if (wooPaymentMethod) {
                wooPaymentMethod.checked = true;
                wooPaymentMethod.dispatchEvent(new Event('change'));
            }
        }

        /**
         * Handle responsive behavior
         */
        handleResponsive() {
            const handleResize = () => {
                this.blocks.forEach(block => {
                    const container = block.container;
                    const width = container.offsetWidth;
                    
                    // Remove previous responsive classes
                    container.classList.remove('newebpay-blocks--mobile', 'newebpay-blocks--tablet');
                    
                    // Add responsive classes
                    if (width < 480) {
                        container.classList.add('newebpay-blocks--mobile');
                    } else if (width < 768) {
                        container.classList.add('newebpay-blocks--tablet');
                    }
                });
            };

            window.addEventListener('resize', handleResize);
            handleResize(); // Initial call
        }

        /**
         * Bind global events
         */
        bindEvents() {
            // Listen for custom events from other scripts
            document.addEventListener('newebpayBlocksRefresh', () => {
                this.refresh();
            });

            // WooCommerce checkout events
            if (this.isWooCommerce) {
                document.body.addEventListener('updated_checkout', () => {
                    this.handleCheckoutUpdate();
                });
            }
        }

        /**
         * Refresh all blocks
         */
        refresh() {
            this.blocks = [];
            this.findBlocks();
            this.initializeBlocks();
            
            if (this.debugMode) {
                console.log('Newebpay Blocks refreshed');
            }
        }

        /**
         * Handle WooCommerce checkout update
         */
        handleCheckoutUpdate() {
            // Refresh blocks after checkout update
            setTimeout(() => this.refresh(), 100);
        }

        /**
         * Track block view for analytics
         */
        trackBlockView(block) {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'newebpay_block_view', {
                    block_id: block.id,
                    layout: block.layout,
                    methods_count: block.methods.length
                });
            }
        }

        /**
         * Track method selection for analytics
         */
        trackMethodSelection(method, block) {
            if (typeof gtag !== 'undefined') {
                gtag('event', 'newebpay_method_selection', {
                    method_id: method.dataset.methodId,
                    method_type: method.dataset.methodType,
                    block_id: block.id
                });
            }
        }

        /**
         * Get public API for external use
         */
        getAPI() {
            return {
                refresh: () => this.refresh(),
                getBlocks: () => this.blocks,
                getSelectedMethods: (blockIndex = 0) => this.getSelectedMethods(this.blocks[blockIndex]),
                selectMethod: (methodId, blockIndex = 0) => this.selectMethodProgrammatically(methodId, blockIndex)
            };
        }

        /**
         * Programmatically select a method
         */
        selectMethodProgrammatically(methodId, blockIndex) {
            const block = this.blocks[blockIndex];
            if (!block) return false;
            
            const method = Array.from(block.methods).find(m => m.dataset.methodId === methodId);
            if (!method) return false;
            
            this.handleMethodClick({ preventDefault: () => {} }, method, block);
            return true;
        }
    }

    /**
     * Initialize when ready
     */
    window.NewebpayBlocksFrontend = new NewebpayBlocksFrontend();
    
    // Expose API globally
    window.newebpayBlocks = window.NewebpayBlocksFrontend.getAPI();

})();
