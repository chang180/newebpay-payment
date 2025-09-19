# AI 快速命令參考

## 🚀 AI 開發快速啟動命令

### 📋 必讀檔案順序
```bash
# 第一次接手專案時的閱讀順序
1. AI-WORKFLOW.md          # 了解工作流程 (2分鐘)
2. AI-CONTEXT.md           # 掌握專案本質 (3分鐘)  
3. AI-CODEBASE-MAP.md      # 理解檔案結構 (2分鐘)
4. 根據任務選擇後續檔案...
```

### 🎯 任務導向快速查詢

#### 新功能開發
```bash
# 讀取順序
AI-CONTEXT.md → AI-CODEBASE-MAP.md → AI-DEVELOPMENT-PATTERNS.md

# 重點關注
- 主要類別架構 (AI-CONTEXT.md)
- 檔案定位 (AI-CODEBASE-MAP.md) 
- 開發模式 (AI-DEVELOPMENT-PATTERNS.md)
```

#### 問題修復
```bash
# 讀取順序  
AI-CONTEXT.md → AI-COMMON-TASKS.md → reports/相關報告

# 重點關注
- 錯誤處理模式 (AI-COMMON-TASKS.md)
- 歷史修復案例 (reports/)
```

#### API 整合
```bash
# 讀取順序
AI-CONTEXT.md → AI-CODEBASE-MAP.md → reports/v1.0.10-technical-specifications.md

# 重點關注
- API 類別位置 (AI-CODEBASE-MAP.md)
- API 詳細規格 (reports/v1.0.10-technical-specifications.md)
```

#### 前端修改
```bash
# 讀取順序
AI-CODEBASE-MAP.md → AI-DEVELOPMENT-PATTERNS.md → AI-COMMON-TASKS.md

# 重點關注
- 前端檔案位置 (AI-CODEBASE-MAP.md)
- JavaScript/CSS 模式 (AI-DEVELOPMENT-PATTERNS.md)
- 前端任務範例 (AI-COMMON-TASKS.md)
```

#### 安全性修改
```bash
# 讀取順序
AI-CONTEXT.md → AI-DEVELOPMENT-PATTERNS.md → reports/相關技術報告

# 重點關注
- 安全架構 (AI-CONTEXT.md)
- 安全開發模式 (AI-DEVELOPMENT-PATTERNS.md)
```

## 🔍 檔案快速定位

### 常用檔案路徑
```php
// 主要類別檔案
includes/class-newebpay-gateway.php     // WooCommerce 閘道主檔案
includes/class-newebpay-wc-blocks.php   // WooCommerce Blocks 整合
includes/nwp/nwpMPG.php                 // 增強型付款閘道

// 前端資源
assets/js/wc-blocks-checkout.js         // Blocks 結帳 JavaScript
assets/css/newebpay-style.css           // 主要樣式檔案

// 設定與語言
languages/newebpay-payment-zh_TW.po     // 繁體中文語言檔
```

### 功能對應檔案
```bash
# 付款流程修改
→ includes/class-newebpay-gateway.php
→ includes/nwp/nwpMPG.php

# 前端介面修改  
→ assets/js/wc-blocks-checkout.js
→ assets/css/newebpay-style.css

# API 通訊修改
→ includes/nwp/nwpMPG.php (主要 API 邏輯)

# 安全性修改
→ includes/class-newebpay-gateway.php (驗證邏輯)
→ includes/nwp/nwpMPG.php (加密處理)
```

## ⚡ 開發模式速查

### PHP 類別架構
```php
// 標準 WordPress 外掛類別
class NewebPayGateway extends WC_Payment_Gateway {
    public function __construct() {
        // 設定初始化
    }
    
    public function process_payment($order_id) {
        // 付款處理邏輯
    }
}
```

### JavaScript 模式
```javascript
// WooCommerce Blocks 整合
const { registerPaymentMethod } = window.wc.wcBlocksRegistry;

registerPaymentMethod({
    name: 'newebpay',
    label: '藍新金流',
    content: NewebPayContent,
    edit: NewebPayContent,
    canMakePayment: () => true,
    ariaLabel: '藍新金流付款選項'
});
```

### Hook 使用
```php
// 常用 WordPress/WooCommerce Hooks
add_action('woocommerce_payment_gateways', 'add_newebpay_gateway');
add_action('woocommerce_api_newebpay', 'handle_newebpay_callback'); 
add_filter('woocommerce_payment_gateway_supports', 'modify_supports');
```

## 🐛 快速除錯

### 常見問題檢查點
```bash
# 支付失敗
1. 檢查 API 金鑰設定
2. 確認加密參數正確
3. 查看錯誤日誌
4. 驗證回調 URL

# Blocks 整合問題
1. 確認 JavaScript 載入
2. 檢查 registerPaymentMethod 呼叫
3. 驗證 REST API 回應
4. 檢查前端 console 錯誤

# VACC/智慧ATM 問題
1. 確認使用 VACC + SourceType 格式
2. 檢查重試機制邏輯
3. 驗證取號回應處理
```

## 💡 效率提示

1. **第一次開發**：按順序讀完前 4 個主要檔案 (約 10 分鐘)
2. **熟悉後開發**：直接查閱 AI-COMMON-TASKS.md 找範例
3. **複雜問題**：查閱 reports/ 目錄尋找歷史解決方案
4. **深度修改**：參考 reports/v1.0.10-technical-specifications.md 了解當前技術規格

這個參考文件讓 AI 能夠用最短時間找到需要的資訊並開始開發。
