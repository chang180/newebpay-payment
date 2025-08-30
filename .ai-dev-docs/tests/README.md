# 🧪 測試工具

這個目錄包含 Newebpay Payment 插件的測試腳本和除錯工具。

## 🔧 測試腳本

### [test-new-payments.php](./test-new-payments.php)
**新支付方式功能測試**
- 測試 Apple Pay、智慧ATM2.0、TWQR 功能
- 驗證支付參數設定
- 檢查前端顯示

### [test-smartpay-integration.php](./test-smartpay-integration.php)
**智慧ATM2.0 專項測試**
- 驗證 SourceType、SourceBankID、SourceAccountNo 參數
- 測試 VACC 支付整合
- API 版本 2.3 兼容性測試

### [debug-payment-flow.php](./debug-payment-flow.php)
**支付流程除錯工具**
- 支付方式資料流程檢查
- 參數傳遞驗證
- 訂單元資料分析

## 📋 使用方式

1. **複製到 WordPress 根目錄**：將需要的測試腳本複製到 WordPress 安裝目錄
2. **在瀏覽器中執行**：直接訪問腳本 URL 進行測試
3. **檢查結果**：根據測試輸出調整設定

## ⚠️ 注意事項

- 這些工具僅供開發測試使用
- 請勿在正式環境中執行
- 測試前請備份重要資料
- 完成測試後請刪除測試腳本

---
> 🔍 這些工具幫助快速診斷和驗證插件功能。
