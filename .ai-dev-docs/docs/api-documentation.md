# API 接口文件

## 藍新金流 API 架構

### 1. 主要 API 端點

#### 測試環境
- 金流閘道: `https://ccore.newebpay.com/MPG/mpg_gateway`
- 電子發票: `https://cinv.ezpay.com.tw/API/invoice_issue`
- 交易查詢: `https://ccore.newebpay.com/API/QueryTradeInfo`

#### 正式環境
- 金流閘道: `https://core.newebpay.com/MPG/mpg_gateway`
- 電子發票: `https://inv.ezpay.com.tw/API/invoice_issue`
- 交易查詢: `https://core.newebpay.com/API/QueryTradeInfo`

### 2. 核心 API 類別

#### nwpOthersAPI 類別
主要功能：
- 交易狀態查詢
- 退款處理
- 電子發票開立
- 其他金流相關 API 操作

主要方法：
- `queryTrade()`: 查詢交易狀態
- `processRefund()`: 處理退款
- `createInvoice()`: 開立電子發票

### 3. 加密處理

#### encProcess 類別
負責處理藍新金流的加密/解密邏輯：
- 使用 AES 加密
- HashKey 和 HashIV 配置
- 數據簽章驗證

### 4. 回調處理

#### 回調 URL 結構
```
{site_url}/?wc-api=WC_newebpay&callback=return
```

#### 處理流程
1. 接收藍新金流回調
2. 解密並驗證數據
3. 更新訂單狀態
4. 觸發相關動作

### 5. 錯誤處理

#### 常見錯誤碼
- `SUCCESS`: 交易成功
- `FAIL`: 交易失敗
- `PENDING`: 交易處理中

#### 日誌記錄
- 使用 WooCommerce 內建日誌系統
- 記錄 API 請求和回應
- 錯誤追蹤和除錯資訊

### 6. 安全性考量

- 所有 API 通訊使用 HTTPS
- 敏感數據加密傳輸
- 回調數據簽章驗證
- 防止重複提交機制
