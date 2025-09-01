# VACC/智慧ATM2.0 取號失敗重試付款修正報告（簡化版）

## 🔍 問題描述

**問題現象：**
當使用 VACC 或智慧ATM2.0 進行取號失敗時（如錯誤代碼 MPG03009），系統沒有正確處理交易失敗狀態，導致：

1. 顯示錯誤訊息：「交易失敗，請重新填單」
2. 同時顯示成功訊息：「好消息！我們已接獲你的訂單...」
3. 無法顯示重試付款按鈕
4. 用戶無法重新進行付款

**根本原因：**
VACC 交易失敗時，藍新金流可能不會回傳 `PaymentType`，導致系統無法進入正確的處理邏輯，也沒有清除 `transaction_id`。

## 🎯 修正策略

**簡化原則：** 不糾結特定錯誤代碼，只要 Status 不是 'SUCCESS' 就進行失敗處理

## 🔧 修正內容

### 1. 統一失敗處理邏輯

**修正位置：** `includes/nwp/nwpMPG.php` - `thankyou_page` 方法

#### 核心修正
```php
// 統一處理所有非成功狀態
if (!empty($req_data['Status']) && $req_data['Status'] != 'SUCCESS') {
    // 交易失敗，清除 transaction_id 以確保可以重試付款
    if (isset($order) && $order) {
        $order->set_transaction_id('');
        $order->update_status('failed', sprintf(
            __('Payment failed via Newebpay. Status: %s, Message: %s', 'newebpay-payment'),
            esc_attr($req_data['Status']),
            esc_attr(urldecode($req_data['Message']))
        ));
    }
    $result = ''; // 空的結果，後面會添加重試區塊
}
```

### 2. 處理流程

1. **檢查 Status 欄位**
2. **如果 Status != 'SUCCESS'** → 觸發失敗處理
3. **清除 transaction_id**
4. **設定訂單狀態為 failed**
5. **顯示重試付款按鈕**

### 3. 適用範圍

此修正適用於所有非成功狀態，包括：
- ✅ MPG03009 - 新増交易訂單主表失敗
- ✅ 網路超時錯誤
- ✅ 參數錯誤  
- ✅ 系統維護中錯誤
- ✅ 任何其他非 SUCCESS 狀態

## ✅ 修正效果

**修正前：**
- 特定錯誤（如MPG03009）可能不會進入正確處理邏輯
- transaction_id 未清除
- 顯示混淆的成功+失敗訊息
- 無法重試付款

**修正後：**
- 任何非 SUCCESS 狀態都會統一處理
- 自動清除 transaction_id
- 設定訂單為失敗狀態
- 顯示重試付款按鈕
- 不顯示混淆訊息

## 🎯 重試付款機制

重試付款按鈕顯示條件：
1. `$order->get_status() === 'failed'`
2. `empty($order->get_transaction_id())`
3. 訂單有效且存在

重試按鈕樣式：
```html
<div class="woocommerce-order-retry-payment">
    <h4>💳 付款失敗</h4>
    <p>您的付款沒有成功完成，請重新嘗試付款。</p>
    <a href="[checkout_payment_url]" class="button alt wc-retry-payment">
        🔄 再試一次付款
    </a>
</div>
```

## 🧪 測試驗證

### 測試場景
測試任何會導致 `Status != 'SUCCESS'` 的情況：
1. **MPG03009**（新増交易訂單主表失敗）
2. **網路超時錯誤**
3. **參數錯誤**
4. **系統維護中錯誤**

### 驗證重點
- ✅ transaction_id 已清除
- ✅ 訂單狀態為 failed
- ✅ 顯示重試付款按鈕
- ✅ 不顯示成功訊息
- ✅ 可以成功重新付款

## 📝 相關檔案

- 主要修正檔案：`includes/nwp/nwpMPG.php`
- 測試檔案：`test-fix-vacc-retry.php`
- 影響範圍：所有支付方式的失敗處理

## 🔄 向後相容性

此修正不會影響：
- 現有的成功付款流程
- 其他支付方式的正常運作
- 已完成的訂單狀態

## 💡 優勢

1. **簡化邏輯**：不需要維護錯誤代碼清單
2. **涵蓋全面**：適用於所有非成功狀態
3. **容易維護**：單一處理邏輯
4. **用戶體驗**：統一的重試機制

---

**修正完成日期：** 2025年9月1日  
**版本：** v1.0.10  
**狀態：** ✅ 完成，採用簡化統一處理邏輯
