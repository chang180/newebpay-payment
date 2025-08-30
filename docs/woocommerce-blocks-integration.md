# Newebpay WooCommerce Blocks 整合說明

## 問題解決

當您在 WooCommerce 區塊結帳中看到以下錯誤訊息時：

> **藍新金流尚不支援此區塊。這可能會影響到買家體驗。**

## 解決方案

我們已經實作了完整的 WooCommerce Blocks 支援：

### 1. 核心整合檔案
- `includes/class-newebpay-wc-blocks.php` - 主要整合類別
- `assets/js/wc-blocks-checkout.js` - 結帳區塊 JavaScript
- `assets/css/wc-blocks-checkout.css` - 結帳區塊樣式

### 2. 功能特性
- ✅ 完全支援 WooCommerce 區塊結帳
- ✅ 響應式設計
- ✅ 深色模式支援
- ✅ 無障礙網頁設計
- ✅ 動畫效果

### 3. 相容性聲明
在 `Central.php` 中已加入：
```php
\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
```

## 使用方式

### 啟用區塊結帳
1. 到 WooCommerce → 設定 → 進階 → 功能
2. 啟用「使用區塊樣式的結帳頁面」
3. 儲存設定

### 設定 Newebpay
1. 到 WooCommerce → 設定 → 付款
2. 找到 Newebpay 並點擊「設定」
3. 啟用付款方式
4. 填入必要資訊：
   - 商店代號
   - HashKey
   - HashIV

## 測試步驟

1. **建立測試商品**
   - 加入購物車
   - 前往結帳頁面

2. **檢查付款方式**
   - 應該看到 Newebpay 選項
   - 不應該出現不支援的錯誤訊息

3. **完成測試訂單**
   - 選擇 Newebpay
   - 填寫結帳資訊
   - 確認可以正常導向付款頁面

## 故障排除

### 如果仍然看到錯誤訊息：

1. **清除快取**
   - 清除所有快取插件
   - 清除瀏覽器快取

2. **檢查插件載入順序**
   - 確保 WooCommerce 先載入
   - 確保沒有衝突的插件

3. **檢查除錯日誌**
   - 啟用 WP_DEBUG
   - 查看 wp-content/debug.log

4. **檢查 JavaScript 控制台**
   - 打開瀏覽器開發者工具
   - 查看控制台是否有錯誤

### 強制重新載入
如果需要強制重新載入整合：
```php
// 在 wp-config.php 中暫時加入
define( 'WP_DEBUG', true );
define( 'SCRIPT_DEBUG', true );
```

## 檔案結構

```
wp-content/plugins/newebpay-payment/
├── Central.php (主檔案 - 已更新)
├── includes/
│   ├── class-newebpay-wc-blocks.php (新增)
│   └── blocks/
│       └── ... (現有檔案)
└── assets/
    ├── js/
    │   └── wc-blocks-checkout.js (新增)
    └── css/
        └── wc-blocks-checkout.css (新增)
```

## 版本資訊

- **版本**: 1.0.10
- **相容性**: WooCommerce 8.0+
- **WordPress**: 6.7+
- **PHP**: 8.0+

## 技術細節

### JavaScript 註冊
```javascript
registerPaymentMethod({
    name: 'newebpay',
    label: 'Newebpay',
    content: NewebpayContent,
    edit: NewebpayEdit,
    canMakePayment: () => true
});
```

### PHP 類別擴展
```php
class Newebpay_Payment_Block extends AbstractPaymentMethodType {
    protected $name = 'newebpay';
    // ...
}
```

現在您的 Newebpay 插件已經完全支援 WooCommerce 區塊結帳系統！
