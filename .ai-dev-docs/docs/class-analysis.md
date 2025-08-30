# 核心類別詳細分析

## 1. WC_Newebpay_Payment (Central.php)

### 類別功能
主要的插件控制器，使用單例模式設計。

### 重要方法
- `get_instance()`: 獲取單例實例
- `init()`: 插件初始化
- `init_gateways()`: 初始化付款閘道
- `add_newebpay_gateway()`: 註冊藍新金流閘道到 WooCommerce
- `init_modules()`: 載入所有必要模組

### 載入的模組
1. `encProcess.php`: 加密處理
2. `nwpMPG.php`: 主要金流功能
3. `nwpElectronicInvoice.php`: 電子發票
4. `nwpOthersAPI.php`: 其他 API 功能

## 2. baseNwpMPG (includes/nwp/baseNwpMPG.php)

### 類別功能
繼承 WC_Payment_Gateway，提供基礎的付款閘道功能。

### 主要動作掛鉤
- `woocommerce_update_options_payment_gateways_` + id
- `woocommerce_thankyou_` + id
- `woocommerce_receipt_` + id
- `woocommerce_api_wc_` + id
- `woocommerce_admin_order_data_after_billing_address`

## 3. WC_newebpay (includes/nwp/nwpMPG.php)

### 類別功能
主要的金流處理類別，繼承自 baseNwpMPG。

### 重要屬性
- `$id`: 'newebpay'
- `$MerchantID`: 商店代號
- `$HashKey`: 串接加密金鑰
- `$HashIV`: 串接加密向量
- `$gateway`: API 閘道網址
- `$TestMode`: 測試模式開關

### 電子發票相關屬性
- `$eiChk`: 電子發票開關
- `$InvMerchantID`: 電子發票商店代號
- `$InvHashKey`: 電子發票加密金鑰
- `$InvHashIV`: 電子發票加密向量
- `$TaxType`: 稅別
- `$eiStatus`: 電子發票狀態
- `$CreateStatusTime`: 開立時間設定

### 核心方法
- `__construct()`: 初始化設定
- `init_form_fields()`: 設定表單欄位
- `process_payment()`: 處理付款
- `receive_response()`: 處理回調
- `thankyou_page()`: 感謝頁面
- `receipt_page()`: 收據頁面

## 4. nwpElectronicInvoice (includes/invoice/nwpElectronicInvoice.php)

### 類別功能
處理電子發票相關功能，使用單例模式。

### 主要功能
- 電子發票開立
- 發票查詢
- 發票設定管理

### 重要屬性
- `$eichk`: 電子發票啟用狀態
- `$invMerchantID`: 發票商店代號
- `$invMerchantIV`: 發票加密向量
- `$taxType`: 稅別設定

## 5. nwpOthersAPI (includes/api/nwpOthersAPI.php)

### 類別功能
處理其他 API 相關功能，繼承自 WC_newebpay。

### 主要功能
- 交易狀態查詢
- 退款處理
- 訂單後台管理功能

### 設定來源
從 WooCommerce 設定中讀取：
- `woocommerce_newebpay_settings`

## 6. encProcess (includes/nwpenc/encProcess.php)

### 類別功能
處理藍新金流的加密和解密功能。

### 主要功能
- AES 加密/解密
- 數據簽章產生
- 簽章驗證

## 7. class-payment-data-processor (includes/processors/class-payment-data-processor.php)

### 類別功能
處理付款數據的驗證和處理。

### 主要功能
- 付款數據驗證
- 數據格式化
- 錯誤處理

## 開發注意事項

1. **單例模式**: 多個類別使用單例模式，注意實例管理
2. **繼承關係**: 理解類別間的繼承關係
3. **設定管理**: 大部分設定存放在 WooCommerce 選項中
4. **動作掛鉤**: 重要功能通過 WordPress 動作掛鉤實現
5. **錯誤處理**: 各類別都有相應的錯誤處理機制
