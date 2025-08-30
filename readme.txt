=== Newebpay Payment ===
Contributors: newebpay
Tags: ecommerce, e-commerce, payment, Newebpay, neweb
Requires at least: 6.7
Tested up to: 6.8
Requires PHP: 8.0
WC requires at least: 8.0
WC tested up to: 10.1
Stable tag: 1.0.10
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

藍新科技金流外掛模組 - 支援 WooCommerce Blocks

== Description ==

藍新金流購物車外掛模組，提供合作特店與個人會員使用開放原始碼商店系統時，透過外掛套件快速完成界接藍新科技金流系統。

✨ **v1.0.10 新功能**：完整支援 WooCommerce Blocks 結帳系統，提供現代化的區塊結帳體驗。

藍新金流整合各種金流工具，讓商店簡易、快速地串接使用。
不論代收模式(指由藍新撥款商店)，或閘道模式(指收單機構撥款商店)，皆能提供商店消費者多元支付方式，讓商店整合帳務輕鬆收款，針對各種支付場景彈性配置。

**結帳系統支援**：
- 傳統 WooCommerce 結帳頁面
- 現代 WooCommerce Blocks 結帳系統
- 完全相容兩種結帳方式

藍新金流提供付款方支付頁面採「響應式網頁設計(Responsive Web Design,簡稱RWD)」。
不論付款方使用之裝置為電腦、平版、手機等不同上網設備，藍新金流付款方支付頁將視付款方瀏覽器之螢幕大小，自動調整至最適合付款方瀏覽及操作之介面，讓交易資訊在不同大小螢幕上都能一目了然。
使操作介面更友善、支付更快速，提升良好的使用者經驗。


= 支付方式 =
- 信用卡(一次付清、分期付款、信用卡紅利)
- 網路ATM
- ATM櫃員機
- 超商代碼
- 超商條碼
- LinePay
- GooglePay
- SamsungPay
- 銀聯卡
- 玉山Wallet
- 台灣Pay
- BitoPay
- 微信支付 (跨境支付方式暫不支援測試模式)
- 支付寶 (跨境支付方式暫不支援測試模式)
- Apple Pay
- 智慧ATM2.0 (需聯繫藍新金流申請支付方式)
- TWQR
- 超商取貨付款
- 超商取貨不付款


= 注意事項 =
- 1.安裝此模組前，請先至藍新科技網站註冊會員，並申請相關收款服務，待服務審核通過後即可使用。
- 2.智慧ATM2.0 為特殊支付方式，需要額外申請。啟用後需設定 SourceType、SourceBankID、SourceAccountNo 三個參數。


= 聯絡我們 =
  藍新金流客服信箱: cs@newebpay.com

== Installation ==

= 系統需求 =

- WordPress 6.7 或更高版本
- WooCommerce 8.0 或更高版本
- PHP version 8.0 or greater
- MySQL version 5.5 or greater

= 自動安裝 =
1. 登入至 WordPress dashboard，點選 "Plugins menu"，選擇 "Add"。
2. 在"search field"中輸入"NewebPay"後點選搜尋。
3. 點擊 "安裝" 即可進行安裝。

= 手動安裝 =
詳細安裝說明請參考 [藍新科技購物車模組](https://www.newebpay.com/website/Page/content/download_api#2)。


== Frequently Asked Questions ==

== Changelog ==

= 1.0.10 =
* 新功能：完整支援 WooCommerce Blocks 結帳系統
* 新功能：現代化區塊結帳介面整合，支援所有付款方式
* 改進：智慧ATM2.0 參數自動轉換 (smartpay ↔ SmartPay)
* 改進：同時支援傳統結帳和 WooCommerce Blocks 結帳
* 改進：JavaScript API 更新為現代 onPaymentSetup 方法
* 改進：完善的錯誤處理和降級機制
* 最佳化：移除開發和調試代碼，生產環境就緒
* 相容性：測試並支援最新的 WooCommerce Blocks 11.0+

= 1.0.9 =
* 新增支付方式：Apple Pay、智慧ATM2.0、TWQR
* 智慧ATM2.0：支援 SourceType、SourceBankID、SourceAccountNo 參數設定
* 更新藍新金流 API 版本至 2.3（支援智慧ATM2.0功能）
* 改善支付方式選擇機制，使用訂單元資料儲存
* 測試並支援 WordPress 6.8 和 WooCommerce 10.1
* 優化後台設定介面說明文字

= 1.0.8 =
支付方式新增 微信支付、支付寶 (跨境支付方式暫不支援測試模式)

v1.0.7
支付方式新增BitoPay

V1.0.6
修正部份類別商品交易後狀態異常

V1.0.5
修正付款完成頁面的文字顯示
修正超商不付款選項不正常的問題
修正電子發票不能使用的問題

V1.0.4
修正下拉選單空白項目

V1.0.3
修正付款完成不會回傳付款結果問題

V1.0.2
支付方式新增取貨付款、取貨不付款功能

v1.0.1
訂單後台功能新增查詢交易狀態按鈕
訂單後台功能新增開立發票按鈕
訂單後台功能執行退款時自動觸發藍新金流退款API

v1.0.0
Official release