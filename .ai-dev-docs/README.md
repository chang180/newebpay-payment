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
- **[v1.0.10 升級計劃](./reports/v1.0.10-block-display-upgrade-plan.md)** - Block Display 支援升級總體規劃
- **[v1.0.10 執行腳本](./reports/v1.0.10-execution-script.md)** - 分階段執行指南與任務清單
- **[v1.0.10 技術規範](./reports/v1.0.10-technical-specifications.md)** - 詳細技術實作規範
- **[智慧ATM2.0整合報告](./reports/智慧ATM2.0整合報告.md)** - v1.0.9 新功能開發完整記錄
- **[README版本更新報告](./reports/README版本更新報告.md)** - WordPress/WooCommerce 版本支援更新
- **[開發報告總覽](./reports/README.md)** - 所有版本開發報告索引

## 🎯 插件概述

**Newebpay Payment** 是一個為 WooCommerce 設計的藍新科技金流整合插件。

### 當前版本：v1.0.9 → 規劃中：v1.0.10
- ✅ **v1.0.9 已完成**：Apple Pay、智慧ATM2.0、TWQR
- ✅ **v1.0.9 已完成**：API 版本 2.3
- ✅ **v1.0.9 已完成**：WordPress 6.8、WooCommerce 10.1 支援
- 🔄 **v1.0.10 規劃中**：Gutenberg Block Display 支援
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