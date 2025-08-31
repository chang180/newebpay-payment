# Newebpay Payment 插件 - AI 輔助開發文檔

> 🤖 這是一個為 AI 輔助開發而設計的文檔目錄，包含了 Newebpay Payment 插件的完整結構分析和開發指南。

## 📁 目錄結構

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
├── tests/                     # 🧪 活躍測試工具
│   ├── test-new-payments.php          # 新支付方式功能測試
│   ├── test-smartpay-integration.php  # 智慧ATM2.0 整合測試
│   ├── debug-payment-flow.php         # 支付流程除錯工具
│   └── README.md                      # 測試工具使用指南
├── fixes/                     # 🔧 重要修復記錄
│   └── php-dynamic-properties-fix.md  # PHP 動態屬性修復
└── reports/                   # 📊 完成報告與參考文檔
    ├── v1.0.10-progress-report-20250830.md  # 最終完成報告
    ├── v1.0.10-technical-specifications.md # 技術規格文檔
    ├── 智慧ATM2.0整合報告.md                  # SmartPay 整合報告
    └── README版本更新報告.md                 # 版本更新摘要
```

## 🎯 v1.0.10 WooCommerce Blocks 整合完成

### ✅ 已完成功能
- **WooCommerce Blocks 結帳整合**: 完整支援 WooCommerce 區塊結帳系統
- **智慧ATM2.0 (SmartPay) 整合**: 參數轉換與相容性處理
- **付款方式選擇**: 支援所有 Newebpay 付款方式 (信用卡、ATM、超商等)
- **相容性保證**: 同時支援傳統結帳和 WooCommerce Blocks
- **生產就緒**: 移除所有調試代碼，適合生產環境使用

### 🔧 核心檔案結構
- `includes/class-newebpay-wc-blocks.php` - WooCommerce Blocks 整合主檔案
- `includes/nwp/nwpMPG.php` - 增強型付款閘道 (支援 Blocks)
- `assets/js/wc-blocks-checkout.js` - 前端 JavaScript 整合

## 🚀 使用指南

### 對於 AI 輔助開發
1. **優先閱讀**: `docs/plugin-structure.md` 了解整體架構
2. **開發參考**: `docs/development-guide.md` 獲取最佳實務
3. **問題排除**: `docs/troubleshooting.md` 解決常見問題
4. **測試工具**: `tests/` 目錄包含實用的測試腳本

### 對於功能開發
- 所有新功能都應遵循現有的 WooCommerce Blocks 整合模式
- 支付方式參數應通過 `process_payment_with_blocks_context` 方法處理
- 確保同時支援傳統表單和 WooCommerce Blocks 結帳流程

## �️ 核心架構概述

**Newebpay Payment** 是一個為 WooCommerce 設計的藍新科技金流整合插件。

### 當前版本：v1.0.10
- ✅ **已完成**：WooCommerce Blocks 完整整合
- ✅ **已完成**：智慧ATM2.0 (SmartPay) 支援
- ✅ **已完成**：Apple Pay、TWQR 整合
- ✅ **已完成**：WordPress 6.8、WooCommerce 10.1 支援
- ✅ **已完成**：PHP 8.0+ 相容性

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

## 清理說明

在 2025-08-31 進行了清理：
- 已將 `.ai-dev-docs/reports/` 與 `.ai-dev-docs/fixes/` 內容移至 `.ai-dev-docs/backups/` 以保留原始紀錄並簡化主要目錄。
- 需要還原或檢視原始報告時，請從 `.ai-dev-docs/backups/` 取回。

## 快速 smoke 測試 (curl 範例)

可以使用下列命令驗證 REST API 是否回傳可用的付款方式（請在 Site 根目錄執行或將 URL 換成您的環境）：

```bash
# 取回 Newebpay Blocks 狀態
curl -sS "http://your-site.local/wp-json/newebpay/v1/status" | jq .

# 取回付款方式
curl -sS "http://your-site.local/wp-json/newebpay/v1/payment-methods" | jq .
```

將 `your-site.local` 替換為您本機或測試環境的主機名稱。

### PowerShell smoke-test

也可使用專用的 PowerShell 腳本進行測試，腳本位於 `.ai-dev-docs/tests/smoke-test.ps1`，使用方式：

```powershell
# 範例：
.\smoke-test.ps1 -SiteUrl 'http://your-site.local'
```

注意事項：
- 若網站使用 HTTPS，請改用 `https://`。
- 在 CI 或自動化環境執行時，請確保可訪問該 Site URL，並在需要時加入憑證驗證或代理設定。
- 腳本會檢查回傳是否包含 `data[].frontend_id` 以及 `cvscom_not_payed` 欄位，若缺少會以 Warning/非 0 exit code 輸出。
