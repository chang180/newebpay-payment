# Newebpay Payment 插件結構分析

## 目錄結構

```
newebpay-payment/
├── Central.php                    # 主要入口檔案，插件初始化
├── readme.txt                     # WordPress 官方插件資訊
├── .ai-dev-docs/                  # AI 輔助開發文件目錄
├── assets/                        # 靜態資源
│   └── js/                        # JavaScript 檔案
├── icon/                          # 插件圖示
├── includes/                      # 核心功能模組
│   ├── api/                       # API 相關功能
│   │   └── nwpOthersAPI.php       # 其他 API 功能類別
│   ├── blocks/                    # 區塊編輯器相關（Gutenberg）
│   ├── invoice/                   # 電子發票功能
│   │   └── nwpElectronicInvoice.php  # 電子發票處理類別
│   ├── nwp/                       # 核心金流功能
│   │   ├── baseNwpMPG.php         # 基礎金流閘道類別
│   │   ├── class-wc-newebpay-v2.php  # 新版金流類別（目前為空）
│   │   ├── nwpMPG.php             # 主要金流處理類別
│   │   └── nwpSetting.php         # 設定相關功能
│   ├── nwpenc/                    # 加密處理模組
│   │   └── encProcess.php         # 加密/解密處理
│   └── processors/                # 數據處理器
│       └── class-payment-data-processor.php  # 付款數據處理器
└── .git/                          # Git 版本控制
```

## 核心類別與功能

### 1. 主要入口類別 (Central.php)
- **WC_Newebpay_Payment**: 單例模式的主要控制類別
- 負責初始化所有模組
- 註冊 WooCommerce 付款閘道
- 支援 WooCommerce HPOS (High-Performance Order Storage)

### 2. 金流核心模組 (includes/nwp/)
- **baseNwpMPG**: 繼承 WC_Payment_Gateway 的基礎類別
- **WC_newebpay**: 主要的金流處理類別，繼承自 baseNwpMPG
- **nwpSetting**: 設定管理相關功能

### 3. 電子發票模組 (includes/invoice/)
- **nwpElectronicInvoice**: 處理電子發票開立、查詢等功能
- 支援單例模式設計

### 4. API 模組 (includes/api/)
- **nwpOthersAPI**: 處理其他 API 功能，如交易查詢、退款等

### 5. 加密模組 (includes/nwpenc/)
- **encProcess**: 處理藍新金流的加密/解密邏輯

### 6. 數據處理器 (includes/processors/)
- **class-payment-data-processor**: 付款數據的處理和驗證

## 支援的付款方式

根據 readme.txt，插件支援以下付款方式：
- 信用卡（一次付清、分期付款、信用卡紅利）
- 網路ATM
- ATM櫃員機
- 超商代碼
- 超商條碼
- LinePay
- GooglePay
- SamsungPay
- 銀聯卡
- 玉山Wallet
- 台灣Pay
- BitoPay
- 微信支付（跨境支付）
- 支付寶（跨境支付）
- 超商取貨付款
- 超商取貨不付款

## 主要功能特性

1. **測試/正式環境切換**: 支援測試模式和正式環境
2. **電子發票整合**: 完整的電子發票開立功能
3. **響應式設計**: 支援 RWD 付款頁面
4. **多元支付**: 整合多種付款方式
5. **訂單管理**: 後台可查詢交易狀態、開立發票、執行退款
6. **高效能訂單儲存**: 支援 WooCommerce HPOS

## 版本資訊

- 當前版本: 1.0.8
- 最低 PHP 版本: 8.0
- 最低 WordPress 版本: 6.4
- 測試至 WooCommerce: 6.6.1

## 開發注意事項

1. 插件使用單例模式設計
2. 遵循 WordPress 和 WooCommerce 開發標準
3. 支援多語言（目前主要為繁體中文）
4. 包含完整的加密安全機制
5. 具備完整的 API 整合功能
