# AI 程式庫檔案映射

## 📁 完整檔案結構與用途

### 根目錄
```
newebpay-payment.php          # 主要外掛啟動檔案
├── 外掛標頭資訊
├── 安全性檢查
├── 常數定義
├── 自動載入設定
└── 外掛啟動邏輯
```

### 核心目錄 `/includes/`
```
includes/
├── class-newebpay-payment.php       # 主要外掛類別
│   ├── 外掛初始化
│   ├── Hook 註冊
│   └── 依賴注入
│
├── class-newebpay-gateway.php       # WooCommerce 閘道類別
│   ├── 閘道設定
│   ├── 支付處理
│   ├── 表單生成
│   └── 回調處理
│
├── class-newebpay-api.php           # API 通訊類別
│   ├── HTTP 請求處理
│   ├── 回應解析
│   └── 錯誤處理
│
├── class-newebpay-security.php      # 安全性類別
│   ├── 資料加密/解密
│   ├── 簽章驗證
│   └── 雜湊計算
│
├── class-newebpay-logger.php        # 日誌記錄類別
│   ├── 檔案日誌
│   ├── 資料庫日誌
│   └── 日誌等級管理
│
├── class-newebpay-admin.php         # 後台管理類別
│   ├── 設定頁面
│   ├── 交易記錄
│   └── 系統狀態
│
├── class-newebpay-validator.php     # 資料驗證類別
│   ├── 輸入驗證
│   ├── 格式檢查
│   └── 安全過濾
│
└── helpers/                         # 輔助函數目錄
    ├── functions.php                # 通用輔助函數
    ├── hooks.php                   # Hook 定義
    └── constants.php               # 常數定義
```

### 範本目錄 `/templates/`
```
templates/
├── payment-form.php                 # 付款表單範本
├── payment-success.php              # 付款成功頁面
├── payment-failure.php              # 付款失敗頁面
├── admin/                          # 後台範本
│   ├── settings-page.php           # 設定頁面
│   ├── transaction-log.php         # 交易記錄
│   └── system-status.php           # 系統狀態
└── emails/                         # 郵件範本
    ├── payment-notification.php     # 付款通知
    └── payment-failure.php         # 付款失敗通知
```

### 靜態資源 `/assets/`
```
assets/
├── css/                            # 樣式檔案
│   ├── admin.css                   # 後台樣式
│   ├── frontend.css                # 前端樣式
│   └── payment-form.css            # 付款表單樣式
│
├── js/                             # JavaScript 檔案
│   ├── admin.js                    # 後台腳本
│   ├── frontend.js                 # 前端腳本
│   └── payment-form.js             # 付款表單腳本
│
└── images/                         # 圖片資源
    ├── logo.png                    # 藍新金流標誌
    └── icons/                      # 付款方式圖示
```

### 語言檔案 `/languages/`
```
languages/
├── newebpay-payment.pot            # 翻譯模板
├── newebpay-payment-zh_TW.po       # 繁體中文
├── newebpay-payment-zh_TW.mo       # 繁體中文編譯檔
├── newebpay-payment-en_US.po       # 英文
└── newebpay-payment-en_US.mo       # 英文編譯檔
```

### 測試檔案 `/tests/`
```
tests/
├── bootstrap.php                   # 測試啟動檔案
├── unit/                          # 單元測試
│   ├── test-gateway.php           # 閘道測試
│   ├── test-api.php               # API 測試
│   ├── test-security.php          # 安全性測試
│   └── test-validator.php         # 驗證器測試
└── integration/                   # 整合測試
    ├── test-payment-flow.php      # 付款流程測試
    └── test-webhook.php           # Webhook 測試
```

### 配置檔案
```
composer.json                       # Composer 配置
package.json                        # NPM 配置
webpack.config.js                   # Webpack 配置
phpunit.xml                         # PHPUnit 配置
.gitignore                          # Git 忽略檔案
README.md                           # 專案說明
CHANGELOG.md                        # 更新日誌
```

## 🎯 關鍵檔案開發指引

### 主要修改檔案
- **功能新增**: `class-newebpay-gateway.php`
- **API 整合**: `class-newebpay-api.php`
- **安全性修改**: `class-newebpay-security.php`
- **後台功能**: `class-newebpay-admin.php`
- **前端樣式**: `assets/css/frontend.css`
- **前端腳本**: `assets/js/frontend.js`

### 配置修改檔案
- **外掛設定**: `newebpay-payment.php`
- **常數定義**: `includes/helpers/constants.php`
- **Hook 定義**: `includes/helpers/hooks.php`

### 範本客製化
- **付款表單**: `templates/payment-form.php`
- **結果頁面**: `templates/payment-*.php`
- **後台介面**: `templates/admin/*.php`

## 🔍 檔案關聯性

### 依賴關係
```
newebpay-payment.php
└── NewebPayPayment
    ├── NewebPayGateway
    │   ├── NewebPayAPI
    │   ├── NewebPaySecurity
    │   └── NewebPayValidator
    ├── NewebPayAdmin
    │   └── NewebPayLogger
    └── NewebPayLogger
```

### 資料流向
```
用戶操作 → Gateway → API → Security → Logger
                    ↓
後台管理 ← Admin ←─────┘
```

這個映射讓 AI 能夠快速定位需要修改的檔案，無需重新掃描整個程式庫結構。
