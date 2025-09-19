# AI 專案上下文

## 🎯 專案本質
- **專案名稱**: NewebPay Payment Gateway for WordPress
- **專案類型**: WordPress 付款閘道外掛
- **主要功能**: 整合藍新金流支付服務
- **技術架構**: PHP + WordPress Plugin Architecture

## 🏗️ 核心架構 (v1.0.10 重構後)

### 外掛檔案結構
```
newebpay-payment/
├── Central.php                    # 主要外掛檔案
├── includes/                      # 核心功能類別
│   ├── nwp/                      # 主要支付閘道
│   │   ├── nwpMPG.php           # 主閘道類別 (重構後)
│   │   ├── nwpSetting.php       # 設定頁面
│   │   └── baseNwpMPG.php       # 基礎類別
│   ├── class-newebpay-*.php     # 新的處理器類別
│   ├── api/                      # API 相關功能
│   ├── invoice/                  # 電子發票功能
│   └── blocks/                   # Gutenberg 區塊支援
├── assets/                       # 靜態資源
│   ├── js/                      # JavaScript 檔案
│   └── css/                     # 樣式檔案
├── languages/                    # 多語言檔案
│   ├── newebpay-payment-zh_TW.po # 繁體中文翻譯
│   ├── newebpay-payment-zh_TW.mo # 繁體中文編譯檔
│   └── newebpay-payment.pot     # 翻譯模板
└── .ai-dev-docs/                # AI 開發文檔
```

### 重構後的類別架構
```
WC_newebpay (主閘道類別)
├── Newebpay_Payment_Handler     # 付款處理邏輯
│   ├── get_payment_args()       # 生成付款參數
│   └── process_payment()        # 處理付款流程
├── Newebpay_Response_Handler     # 回應處理邏輯
│   ├── handle_callback_response() # 處理回調
│   ├── handle_order_received_text() # 訂單接收文字
│   └── chkShaIsVaildByReturnData() # SHA 驗證
├── Newebpay_Form_Handler        # 表單處理邏輯
│   ├── display_payment_fields() # 顯示付款欄位
│   ├── validate_fields()        # 驗證欄位
│   ├── display_receipt_page()   # 顯示收據頁面
│   └── generate_payment_form() # 生成付款表單
├── Newebpay_Order_Handler       # 訂單處理邏輯
│   └── display_admin_order_fields() # 後台訂單欄位
├── Newebpay_Blocks_Handler      # Blocks 整合邏輯
│   ├── register_blocks()        # 註冊區塊
│   └── render_payment_block()   # 渲染付款區塊
├── Newebpay_Validator           # 資料驗證工具
│   └── validate_payment_data()  # 驗證付款資料
├── Newebpay_Error_Handler       # 錯誤處理工具
│   ├── handle_error()           # 處理錯誤
│   ├── handle_validation_error() # 處理驗證錯誤
│   └── log_exception()          # 記錄例外
├── Newebpay_Cart_Manager        # 購物車管理工具
│   ├── clear_cart_for_order()   # 清空購物車
│   └── on_payment_complete()    # 付款完成處理
└── Newebpay_Performance_Optimizer # 效能優化工具
    ├── conditional_script_loading() # 條件載入腳本
    └── cache_management()        # 快取管理
```

## 🛡️ 現代化功能 (v1.0.10+)

### WooCommerce Blocks 支援
- 完整支援 WooCommerce 新式區塊結帳
- 相容性聲明：`cart_checkout_blocks` 和 `custom_order_tables`
- 響應式設計和無障礙網頁設計
- 支援所有支付方式在區塊結帳中運作

### 多語言本地化
- **Text Domain**: `newebpay-payment`
- **完整繁體中文翻譯**: 所有使用者介面、錯誤訊息、狀態通知
- **翻譯覆蓋範圍**: 
  - 後台管理介面
  - 前台結帳流程
  - API 回應訊息
  - 電子發票功能
- **WordPress 標準**: 使用 `load_plugin_textdomain()` 和標準 `.po/.mo` 檔案

## 🔌 WordPress 整合點

### WooCommerce 整合
- 繼承 `WC_Payment_Gateway` 類別
- 實作支付閘道標準介面
- 處理訂單狀態更新

### WordPress Hooks
- `woocommerce_payment_gateways` - 註冊支付閘道
- `woocommerce_api_*` - API 回調處理
- `admin_menu` - 後台選單
- `wp_enqueue_scripts` - 前端資源載入

## 🔐 藍新金流整合

### API 端點
- **測試環境**: `https://ccore.newebpay.com/`
- **正式環境**: `https://core.newebpay.com/`

### 支付流程
1. 客戶選擇藍新付款
2. 生成加密支付資料
3. 導向藍新付款頁面
4. 藍新回調處理結果
5. 更新訂單狀態

### 加密機制
- 使用 AES-256-CBC 加密
- SHA-256 雜湊驗證
- 時間戳防重播攻擊

## 🛠️ 開發環境要求

### 技術需求
- PHP 7.4+
- WordPress 5.0+
- WooCommerce 3.0+
- SSL 證書（正式環境）

### 開發工具
- Composer（依賴管理）
- PHPUnit（單元測試）
- WordPress Coding Standards

## 🚨 安全考量

### 資料保護
- 敏感資料不儲存在資料庫
- API 金鑰加密儲存
- 交易資料即時加密

### 驗證機制
- 來源 IP 驗證
- 簽章驗證
- 時間戳驗證

## 📝 設定與配置

### 必要設定
- 商店代號 (MerchantID)
- HashKey
- HashIV
- 環境設定（測試/正式）

### 可選設定
- 付款方式選擇
- 回調 URL 設定
- 日誌記錄等級
- 錯誤處理方式

## 🔍 常見整合點

### 前端整合
- 結帳頁面付款選項
- 付款結果頁面
- 錯誤訊息顯示

### 後端整合
- 訂單管理介面
- 交易記錄查詢
- 設定頁面
- 日誌檢視

## 🎨 客製化要點

### 樣式客製化
- 使用 WordPress 標準樣式
- 支援主題客製化
- RWD 響應式設計

### 功能擴展
- Hook 系統支援
- Filter 機制
- Action 觸發點

## 🏗️ 重構優勢 (v1.0.10)

### 單一職責原則
- 每個處理器類別專注特定功能
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

這些資訊構成了 AI 開發此專案的核心上下文，無需重新分析整個程式庫即可開始開發工作。