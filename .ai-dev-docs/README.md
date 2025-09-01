# Newebpay Payment 藍新金流 WordPress 外掛開發文檔

## 最新狀態
- **版本**: v1.0.10
- **狀態**: ✅ 生產環境就緒 - WooCommerce Blocks 完全整合
- **最後更新**: 2025-09-01
- **最新修復**: VACC/智慧ATM2.0 取號失敗重試付款機制

## 最新開發報告
- 📄 [VACC取號失敗重試付款修正報告](reports/VACC取號失敗重試付款修正報告.md) - 2025-09-01
- 📄 [v1.0.10 WooCommerce Blocks 支付方式傳遞修復](reports/v1.0.10-blocks-payment-fix.md) - 2025-01-09

### 🔧 核心檔案結構
- `includes/class-newebpay-wc-blocks.php` - WooCommerce Blocks 整合主檔案
- `includes/nwp/nwpMPG.php` - 增強型付款閘道 (支援 Blocks + 失敗重試)
- `assets/js/wc-blocks-checkout.js` - 前端 JavaScript 整合

## 修復狀態

### ✅ 已完成並生產就緒
- 插件基本結構和 WooCommerce 整合
- 信用卡、ATM轉帳、LINE Pay、超商代碼等支付方式
- 藍新金流 API 整合和回調處理
- WooCommerce Blocks 結帳完全整合
- **智慧ATM2.0 正確參數格式** (VACC + SourceType)
- **支付方式選擇傳遞問題徹底解決**
- **前端與後端資料流完全打通**
- **統一失敗處理機制** - 任何非 SUCCESS 狀態自動觸發重試付款
- **VACC/智慧ATM2.0 取號失敗重試機制**
- **生產環境代碼清潔**
- **同時支援傳統結帳和 WooCommerce Blocks**

### 📋 待改進項目
- 考慮增加自動化測試覆蓋
- 優化藍新金流回應的用戶體驗
- 研究 WooCommerce 未來版本的相容性

## 🚀 使用指南

### 對於 AI 輔助開發
1. **優先閱讀**: `docs/plugin-structure.md` 了解整體架構
2. **開發參考**: `docs/development-guide.md` 獲取最佳實務
3. **問題排除**: `docs/troubleshooting.md` 解決常見問題
4. **測試工具**: `tests/` 目錄包含實用的測試腳本

### 對於功能開發
- 所有新功能都應遵循現有的 WooCommerce Blocks 整合模式
- 智慧ATM2.0 必須使用 VACC + SourceType 格式，不是 SMARTPAY
- 支付方式驗證時注意大小寫處理

## 📚 關鍵文檔

### 開發流程
1. **規劃** → 檢閱 `docs/development-guide.md`
2. **實作** → 參考 `docs/code-snippets.md`
3. **測試** → 使用 `tests/` 目錄工具
4. **問題排除** → 使用 `docs/troubleshooting.md`
5. **版本記錄** → 查閱 `reports/` 目錄

## 📞 技術支援

- 藍新金流客服: cs@newebpay.com
- API 文檔: https://www.newebpay.com/website/Page/content/download_api#2

---

> 💡 **提示**: 建議按照目錄結構順序閱讀文檔，先從 `docs/plugin-structure.md` 開始。

## 最新修復技術要點

### WooCommerce Blocks 資料流
1. 前端用戶選擇支付方式
2. JavaScript 處理 `onPaymentSetup` 事件
3. 透過 `paymentMethodData` 傳遞到後端
4. `process_payment_with_blocks_context` 處理資料
5. 設定 `$_POST` 變數給傳統流程使用
6. `validate_fields` 驗證並轉換支付方式
7. `get_newebpay_args` 生成最終 API 參數

### 智慧ATM2.0 特殊處理
- **不是** 使用 `SMARTPAY = 1`
- **正確格式**: `VACC = 1` + `SourceType = 4`
- 需要額外的 `SourceBankID` 和 `SourceAccountNo` 參數

### 支付方式對應
```php
$payment_config_map = array(
    'credit' => 'Credit',
    'vacc' => 'Vacc', 
    'smartpay' => 'SmartPay',  // 注意駝峰式
    'linepay' => 'LINEPAY',
    'applepay' => 'APPLEPAY',
    // ...
);
```

## 快速驗證測試

```bash
# 檢查 REST API 狀態
curl -sS "http://your-site.local/wp-json/newebpay/v1/status" | jq .

# 檢查付款方式
curl -sS "http://your-site.local/wp-json/newebpay/v1/payment-methods" | jq .
```

將 `your-site.local` 替換為您的開發環境主機名稱。

---

**版本**: v1.0.10  
**狀態**: ✅ 生產環境就緒  
**最後驗證**: 2025-01-09
