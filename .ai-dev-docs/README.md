# Newebpay Payment 插件 - AI 輔助開發文檔

> 🤖 這是一個為 AI 輔助開發而設計的文檔目錄，包含了 Newebpay Payment 插件的完整結構分析和開發指南。

## 📋 文檔目錄

### 核心文檔
- **[plugin-structure.md](./plugin-structure.md)** - 插件整體結構分析
- **[class-analysis.md](./class-analysis.md)** - 核心類別詳細分析  
- **[api-documentation.md](./api-documentation.md)** - API 接口文檔
- **[development-guide.md](./development-guide.md)** - 開發指南與最佳實務

### 開發資源
- **[code-snippets.md](./code-snippets.md)** - 常用程式碼片段與範例
- **[troubleshooting.md](./troubleshooting.md)** - 問題排除指南

## 🎯 插件概述

**Newebpay Payment** 是一個為 WooCommerce 設計的藍新科技金流整合插件，支援多種付款方式和電子發票功能。

### 主要功能
- ✅ 多元付款方式（信用卡、ATM、超商、行動支付等）
- ✅ 電子發票整合
- ✅ 測試/正式環境切換
- ✅ 響應式付款頁面
- ✅ 完整的後台管理功能
- ✅ 支援 WooCommerce HPOS

### 技術架構
- **版本**: 1.0.8
- **PHP要求**: 8.0+
- **WordPress要求**: 6.4+
- **WooCommerce要求**: 6.4+

## 🏗️ 核心架構

```
newebpay-payment/
├── Central.php                 # 主入口，插件初始化
├── includes/
│   ├── nwp/                   # 核心金流功能
│   │   ├── baseNwpMPG.php     # 基礎閘道類別
│   │   ├── nwpMPG.php         # 主要金流處理
│   │   └── nwpSetting.php     # 設定管理
│   ├── api/                   # API 功能模組
│   │   └── nwpOthersAPI.php   # 交易查詢、退款等
│   ├── invoice/               # 電子發票模組
│   │   └── nwpElectronicInvoice.php
│   ├── nwpenc/               # 加密處理模組
│   │   └── encProcess.php
│   └── processors/           # 數據處理器
│       └── class-payment-data-processor.php
├── assets/                   # 前端資源
└── .ai-dev-docs/            # AI 開發文檔
```

## 🔧 快速開始

### 1. 理解插件結構
開始前請先閱讀 [plugin-structure.md](./plugin-structure.md) 了解整體架構。

### 2. 核心類別分析
查看 [class-analysis.md](./class-analysis.md) 深入了解各個類別的功能和關係。

### 3. API 集成
參考 [api-documentation.md](./api-documentation.md) 了解與藍新金流的 API 整合。

### 4. 開發實務
遵循 [development-guide.md](./development-guide.md) 的最佳實務進行開發。

## 🚀 主要類別簡介

### WC_Newebpay_Payment (Central.php)
- 單例模式的主控制器
- 負責插件初始化和模組載入
- 註冊 WooCommerce 付款閘道

### WC_newebpay (nwpMPG.php)  
- 繼承 WC_Payment_Gateway
- 處理付款流程和設定管理
- 支援多種付款方式

### nwpElectronicInvoice
- 電子發票功能處理
- 發票開立、查詢、管理
- 單例模式設計

### nwpOthersAPI
- 交易狀態查詢
- 退款處理
- 其他 API 操作

## 📊 支援的付款方式

| 類別 | 付款方式 |
|------|----------|
| 信用卡 | 一次付清、分期付款、紅利折抵 |
| 銀行轉帳 | 網路ATM、ATM櫃員機 |
| 超商 | 代碼繳費、條碼繳費、取貨付款、取貨不付款 |
| 行動支付 | LinePay、GooglePay、SamsungPay、台灣Pay |
| 電子錢包 | 玉山Wallet、BitoPay |
| 國際支付 | 銀聯卡、微信支付、支付寶 |

## 🛠️ 開發工具

### 測試環境
- 測試 API: `https://ccore.newebpay.com/MPG/mpg_gateway`
- 發票測試: `https://cinv.ezpay.com.tw/API/invoice_issue`

### 除錯工具
- WooCommerce 狀態頁面
- WordPress 除錯日誌
- 藍新金流後台交易記錄

## 📝 開發注意事項

1. **單例模式**: 多個核心類別使用單例模式
2. **加密安全**: 所有 API 通訊都經過加密處理  
3. **回調處理**: 正確處理藍新金流的回調通知
4. **錯誤處理**: 完善的錯誤處理和日誌記錄
5. **測試**: 充分測試測試環境和正式環境

## 🔄 版本歷程

- **v1.0.8**: 新增微信支付、支付寶
- **v1.0.7**: 新增 BitoPay
- **v1.0.6**: 修正交易狀態異常
- **v1.0.5**: 修正付款完成頁面和電子發票問題
- **v1.0.4**: 修正下拉選單問題
- **v1.0.3**: 修正付款完成回傳問題  
- **v1.0.2**: 新增取貨付款功能
- **v1.0.1**: 新增後台管理功能
- **v1.0.0**: 初始發布

## 📞 技術支援

- 藍新金流客服: cs@newebpay.com
- API 文檔: https://www.newebpay.com/website/Page/content/download_api#2

---

> 💡 **提示**: 在進行任何開發工作前，建議先熟悉相關文檔，並在測試環境中驗證功能。