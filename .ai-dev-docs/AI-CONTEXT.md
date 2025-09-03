# AI 專案上下文

## 🎯 專案本質
- **專案名稱**: NewebPay Payment Gateway for WordPress
- **專案類型**: WordPress 付款閘道外掛
- **主要功能**: 整合藍新金流支付服務
- **技術架構**: PHP + WordPress Plugin Architecture

## 🏗️ 核心架構

### 外掛檔案結構
```
newebpay-payment/
├── Central.php                    # 主要外掛檔案 (重新命名)
├── includes/                      # 核心功能類別
│   ├── nwp/                      # 主要支付閘道
│   ├── api/                      # API 相關功能
│   ├── invoice/                  # 電子發票功能
│   ├── blocks/                   # Gutenberg 區塊支援
│   ├── class-newebpay-wc-blocks.php  # WooCommerce Blocks 整合
│   └── class-newebpay-logger.php # 日誌記錄
├── assets/                       # 靜態資源
│   ├── js/                      # JavaScript 檔案
│   └── css/                     # 樣式檔案
├── languages/                    # 多語言檔案
│   ├── newebpay-payment-zh_TW.po # 繁體中文翻譯
│   ├── newebpay-payment-zh_TW.mo # 繁體中文編譯檔
│   └── newebpay-payment.pot     # 翻譯模板
└── .ai-dev-docs/                # AI 開發文檔
```

### 主要類別架構
```
WC_Newebpay_Payment              # 主要外掛類別
├── WC_newebpay                  # WooCommerce 閘道整合
├── Newebpay_WooCommerce_Blocks_Integration # WC Blocks 支援
├── Newebpay_Blocks              # Gutenberg 區塊支援  
├── NewebPayAPI                  # API 通訊處理
├── NewebPayLogger               # 日誌記錄
└── nwpElectronicInvoice        # 電子發票功能
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

這些資訊構成了 AI 開發此專案的核心上下文，無需重新分析整個程式庫即可開始開發工作。
