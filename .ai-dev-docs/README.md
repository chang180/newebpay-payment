# Newebpay Payment 插件 - AI 輔助開發文檔

> 🤖 這是一個為 AI 輔助開發而設計的文檔目錄，包含了 Newebpay Payment 插件的完整結構分析和開發指南。

## � 目錄結構

```
.ai-dev-docs/
├── README.md                  # 總覽文檔 (本文件)
├── docs/                      # 📚 核心開發文檔
│   ├── plugin-structure.md    # 插件整體結構分析
│   ├── class-analysis.md      # 核心類別詳細分析  
│   ├── api-documentation.md   # API 接口文檔
│   ├── development-guide.md   # 開發指南與最佳實務
│   ├── code-snippets.md      # 常用程式碼片段與範例
│   └── troubleshooting.md    # 問題排除指南
├── tests/                     # 🧪 測試工具與腳本
│   ├── test-new-payments.php          # 新支付方式功能測試
│   ├── test-smartpay-integration.php  # 智慧ATM2.0 整合測試
│   └── debug-payment-flow.php         # 支付流程除錯工具
└── reports/                   # 📊 版本開發報告
    ├── v1.0.11-ATM轉帳顯示修正報告.md # v1.0.11 ATM 轉帳顯示修正
    ├── 智慧ATM2.0整合報告.md          # v1.0.9 智慧ATM2.0 功能整合
    └── README版本更新報告.md          # readme.txt 版本支援度更新
```

## 🚀 快速導航

### 📚 開發文檔 (`docs/`)
- **[plugin-structure.md](./docs/plugin-structure.md)** - 從這裡開始了解插件整體架構
- **[class-analysis.md](./docs/class-analysis.md)** - 深入了解各個核心類別
- **[api-documentation.md](./docs/api-documentation.md)** - 藍新金流 API 整合文檔
- **[development-guide.md](./docs/development-guide.md)** - 開發最佳實務與規範
- **[code-snippets.md](./docs/code-snippets.md)** - 常用程式碼範例
- **[troubleshooting.md](./docs/troubleshooting.md)** - 常見問題解決方案

### 🧪 測試工具 (`tests/`)
- **[test-new-payments.php](./tests/test-new-payments.php)** - 新支付方式功能驗證
- **[test-smartpay-integration.php](./tests/test-smartpay-integration.php)** - 智慧ATM2.0 專項測試
- **[debug-payment-flow.php](./tests/debug-payment-flow.php)** - 支付流程除錯分析

### 📊 開發報告 (`reports/`)
- **[v1.0.11-ATM轉帳顯示修正報告.md](./reports/v1.0.11-ATM轉帳顯示修正報告.md)** - v1.0.11 ATM 轉帳顯示修正
- **[智慧ATM2.0整合報告.md](./reports/智慧ATM2.0整合報告.md)** - v1.0.9 新功能開發完整記錄
- **[README版本更新報告.md](./reports/README版本更新報告.md)** - WordPress/WooCommerce 版本支援更新

## 🎯 插件概述

**Newebpay Payment** 是一個為 WooCommerce 設計的藍新科技金流整合插件。

### 當前版本：v1.0.12
- ✅ 修正：ATM 轉帳取號成功頁面顯示問題
- ✅ 修正：信用卡分期付款 InstFlag 參數設定
- ✅ 改善：ATM 轉帳訂單狀態處理邏輯
- ✅ 更新：API 版本 2.3
- ✅ 支援：WordPress 6.8、WooCommerce 10.1
- ✅ 要求：PHP 8.0+

## 🏗️ 核心架構

```
newebpay-payment/
├── Central.php                 # 主入口
├── includes/nwp/              # 核心金流功能
├── includes/api/              # API 功能模組  
├── includes/invoice/          # 電子發票模組
├── includes/nwpenc/          # 加密處理模組
└── .ai-dev-docs/             # 開發文檔 (本目錄)
```

## 🔧 開發指引

1. **新手入門** → 閱讀 `docs/plugin-structure.md`
2. **深入開發** → 參考 `docs/development-guide.md`  
3. **API 整合** → 查看 `docs/api-documentation.md`
4. **問題排除** → 使用 `tests/` 目錄中的工具
5. **版本記錄** → 查閱 `reports/` 目錄

## 📞 技術支援

- 藍新金流客服: cs@newebpay.com
- API 文檔: https://www.newebpay.com/website/Page/content/download_api#2

---

> 💡 **提示**: 建議按照目錄結構順序閱讀文檔，先從 `docs/plugin-structure.md` 開始。