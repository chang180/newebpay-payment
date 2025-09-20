# NeWebPay Payment 測試文檔

## 🧪 測試環境設置

### 快速開始

```bash
# 1. 安裝依賴
composer install

# 2. 執行簡化測試（推薦）
php tests/run-tests.php

# 3. 執行完整 PHPUnit 測試
composer test
```

### 測試結構

```
tests/
├── bootstrap.php              # 測試環境啟動檔案
├── phpunit-stubs.php         # IDE 類型定義
├── run-tests.php             # 簡化測試執行器
├── setup-test-env.sh         # 測試環境設置腳本
├── test-newebpay-validator.php # Validator 單元測試
└── README.md                 # 測試文檔
```

## 🔧 測試配置

### phpunit.xml
- 測試框架配置
- 覆蓋率報告設置
- 測試目錄指定

### composer.json
- 測試依賴管理
- 測試腳本定義
- 程式碼標準檢查

## 📊 測試覆蓋率

執行測試後會生成覆蓋率報告：
- HTML 報告：`tests/coverage/index.html`
- XML 報告：`tests/coverage/clover.xml`

## 🚀 可用命令

```bash
# 執行簡化測試（推薦）
php tests/run-tests.php

# 執行所有測試
composer test

# 生成覆蓋率報告
composer test:coverage

# 程式碼風格檢查
composer phpcs

# 自動修復程式碼風格
composer phpcbf

# 設置測試環境
composer test:setup
```

## 📝 測試類別

### Test_Newebpay_Validator
測試 `Newebpay_Validator` 類別的所有方法：

- `test_validate_payment_data()` - 付款資料驗證
- `test_validate_payment_data_invalid()` - 無效付款資料處理
- `test_validate_amount()` - 金額驗證
- `test_validate_order_id()` - 訂單 ID 驗證
- `test_validate_payment_method()` - 付款方式驗證
- `test_validate_callback_data()` - 回調資料驗證
- `test_validate_amount_boundaries()` - 金額邊界值測試
- `test_item_description_length_limit()` - 商品描述長度限制
- `test_sanitize_payment_data()` - 資料清理功能
- `test_empty_values_handling()` - 空值處理
- `test_invalid_status_values()` - 無效狀態值測試
- `test_valid_status_values()` - 有效狀態值測試

## 🔍 測試最佳實踐

1. **單一職責**：每個測試方法只測試一個功能點
2. **邊界值測試**：測試最小/最大值和邊界情況
3. **異常處理**：確保異常情況被正確處理
4. **資料清理**：測試輸入資料的安全清理
5. **狀態驗證**：確保所有狀態值都被正確驗證

## 🐛 問題排除

### 常見問題

1. **WordPress 測試框架未找到**
   ```bash
   composer test:setup
   ```

2. **資料庫連接失敗**
   - 檢查 MySQL 服務是否運行
   - 確認資料庫用戶權限

3. **記憶體不足**
   - 增加 PHP 記憶體限制
   - 檢查測試資料大小

### 調試模式

在 `phpunit.xml` 中啟用詳細輸出：
```xml
<phpunit verbose="true" stopOnFailure="true">
```

## 📚 相關文檔

- [WordPress 測試文檔](https://make.wordpress.org/core/handbook/testing/automated-testing/)
- [PHPUnit 文檔](https://phpunit.readthedocs.io/)
- [WooCommerce 測試指南](https://woocommerce.com/developers/testing/)

## 💡 推薦使用方式

### 開發階段
```bash
# 快速測試（推薦）
php tests/run-tests.php
```

### 正式測試
```bash
# 完整測試環境
composer test
```

### CI/CD 整合
```bash
# 自動化測試
composer test:coverage
```