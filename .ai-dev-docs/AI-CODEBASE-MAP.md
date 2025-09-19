# AI 程式庫檔案映射

## 📁 完整檔案結構與用途 (v1.0.10 重構後)

### 根目錄
```
newebpay-payment/
├── Central.php                    # 主要外掛啟動檔案
│   ├── 外掛標頭資訊
│   ├── 安全性檢查
│   ├── 常數定義
│   ├── 類別載入
│   └── 外掛啟動邏輯
└── .ai-dev-docs/                 # AI 開發文檔
    ├── README.md                  # 主要說明
    ├── AI-CONTEXT.md           # 專案上下文
    ├── AI-CODEBASE-MAP.md      # 檔案映射 (本檔案)
    ├── AI-DEVELOPMENT-PATTERNS.md # 開發模式
    ├── AI-COMMON-TASKS.md      # 常見任務
    ├── AI-WORKFLOW.md          # 工作流程
    └── AI-QUICK-REFERENCE.md   # 快速參考
```

### 核心目錄 `/includes/`
```
includes/
├── nwp/                         # 主要支付閘道目錄
│   ├── nwpMPG.php              # 主閘道類別 (重構後)
│   │   ├── 閘道設定
│   │   ├── 處理器初始化
│   │   ├── 委派方法調用
│   │   └── 核心支付邏輯
│   ├── nwpSetting.php          # 設定頁面
│   │   ├── 表單欄位定義
│   │   ├── 驗證規則
│   │   └── 設定選項
│   └── baseNwpMPG.php          # 基礎類別
│       └── 共同屬性和方法
│
├── class-newebpay-payment-handler.php    # 付款處理器
│   ├── get_payment_args()       # 生成付款參數
│   ├── process_payment()        # 處理付款流程
│   └── 智慧ATM2.0 特殊處理
│
├── class-newebpay-response-handler.php  # 回應處理器
│   ├── handle_callback_response() # 處理回調
│   ├── handle_order_received_text() # 訂單接收文字
│   ├── chkShaIsVaildByReturnData() # SHA 驗證
│   └── 交易狀態處理
│
├── class-newebpay-form-handler.php     # 表單處理器
│   ├── display_payment_fields() # 顯示付款欄位
│   ├── validate_fields()        # 驗證欄位
│   ├── display_receipt_page()   # 顯示收據頁面
│   ├── generate_payment_form() # 生成付款表單
│   └── 重試付款按鈕控制
│
├── class-newebpay-order-handler.php    # 訂單處理器
│   ├── display_admin_order_fields() # 後台訂單欄位
│   └── 訂單管理功能
│
├── class-newebpay-blocks-handler.php   # Blocks 整合處理器
│   ├── register_blocks()        # 註冊區塊
│   ├── render_payment_block()   # 渲染付款區塊
│   └── WooCommerce Blocks 支援
│
├── class-newebpay-validator.php         # 資料驗證工具
│   ├── validate_payment_data()  # 驗證付款資料
│   ├── 必要欄位檢查
│   ├── 資料類型驗證
│   └── 安全過濾
│
├── class-newebpay-error-handler.php    # 錯誤處理工具
│   ├── handle_error()           # 處理錯誤
│   ├── handle_validation_error() # 處理驗證錯誤
│   ├── handle_security_error()   # 處理安全錯誤
│   ├── log_exception()          # 記錄例外
│   └── 統一錯誤處理機制
│
├── class-newebpay-cart-manager.php     # 購物車管理工具
│   ├── clear_cart_for_order()   # 清空購物車
│   ├── on_payment_complete()    # 付款完成處理
│   ├── on_order_status_processing() # 訂單處理中
│   ├── on_order_status_completed() # 訂單完成
│   ├── on_order_status_failed() # 訂單失敗
│   └── 多層保護機制
│
├── class-newebpay-performance-optimizer.php # 效能優化工具
│   ├── conditional_script_loading() # 條件載入腳本
│   ├── cache_management()        # 快取管理
│   ├── hook_optimization()        # Hook 優化
│   └── 資源優化
│
├── api/                         # API 相關功能
│   ├── class-newebpay-api.php    # API 通訊類別
│   ├── HTTP 請求處理
│   ├── 回應解析
│   └── 錯誤處理
│
├── invoice/                     # 電子發票功能
│   ├── class-nwp-electronic-invoice.php # 電子發票類別
│   ├── 發票開立
│   ├── 發票查詢
│   └── 發票作廢
│
└── blocks/                      # Gutenberg 區塊支援
    ├── class-newebpay-blocks.php # 區塊類別
    ├── 區塊註冊
    ├── 區塊渲染
    └── 前端腳本
```

### 靜態資源 `/assets/`
```
assets/
├── css/                         # 樣式檔案
│   ├── admin.css               # 後台樣式
│   ├── frontend.css            # 前端樣式
│   └── payment-form.css        # 付款表單樣式
│
├── js/                          # JavaScript 檔案
│   ├── admin/                   # 後台腳本
│   │   └── newebpaySetting.js  # 設定頁面腳本
│   ├── frontend.js             # 前端腳本
│   └── payment-form.js          # 付款表單腳本
│
└── images/                      # 圖片資源
    ├── newebpay.png            # 藍新金流標誌
    └── icons/                  # 付款方式圖示
```

### 語言檔案 `/languages/`
```
languages/
├── newebpay-payment.pot         # 翻譯模板
├── newebpay-payment-zh_TW.po    # 繁體中文
├── newebpay-payment-zh_TW.mo    # 繁體中文編譯檔
├── newebpay-payment-en_US.po    # 英文
└── newebpay-payment-en_US.mo    # 英文編譯檔
```

### 測試檔案 `/tests/`
```
tests/
├── test-newebpay-validator.php  # 驗證器測試
├── bootstrap.php                # 測試啟動檔案
├── unit/                        # 單元測試
│   ├── test-gateway.php        # 閘道測試
│   ├── test-api.php            # API 測試
│   ├── test-security.php       # 安全性測試
│   └── test-validator.php       # 驗證器測試
└── integration/                 # 整合測試
    ├── test-payment-flow.php   # 付款流程測試
    └── test-webhook.php        # Webhook 測試
```

## 🎯 關鍵檔案開發指引

### 主要修改檔案
- **功能新增**: `class-newebpay-*-handler.php`
- **API 整合**: `class-newebpay-api.php`
- **安全性修改**: 各處理器類別
- **後台功能**: `class-newebpay-order-handler.php`
- **前端樣式**: `assets/css/frontend.css`
- **前端腳本**: `assets/js/frontend.js`

### 配置修改檔案
- **外掛設定**: `Central.php`
- **閘道設定**: `includes/nwp/nwpMPG.php`
- **設定頁面**: `includes/nwp/nwpSetting.php`

### 範本客製化
- **付款表單**: 各處理器類別
- **結果頁面**: `class-newebpay-response-handler.php`
- **後台介面**: `class-newebpay-order-handler.php`

## 🔍 檔案關聯性 (重構後)

### 依賴關係
```
Central.php
└── WC_newebpay (nwpMPG.php)
    ├── Newebpay_Payment_Handler
    │   └── Newebpay_Validator
    ├── Newebpay_Response_Handler
    │   └── Newebpay_Error_Handler
    ├── Newebpay_Form_Handler
    │   └── Newebpay_Validator
    ├── Newebpay_Order_Handler
    ├── Newebpay_Blocks_Handler
    ├── Newebpay_Cart_Manager
    └── Newebpay_Performance_Optimizer
```

### 資料流向
```
用戶操作 → WC_newebpay → 處理器類別 → 工具類別
                    ↓
後台管理 ← Order_Handler ←─────┘
```

## 🏗️ 重構後的架構優勢

### 單一職責原則
- 每個處理器專注特定功能
- 清晰的職責分離
- 易於維護和測試

### 錯誤隔離
- 問題定位更精確
- 獨立的錯誤處理
- 更好的除錯體驗

### 效能優化
- 條件載入機制
- 快取管理
- 資源優化

### 可擴展性
- 易於添加新功能
- 模組化設計
- 標準化介面

這個映射讓 AI 能夠快速定位需要修改的檔案，無需重新掃描整個程式庫結構。