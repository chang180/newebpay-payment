=== Newebpay Payment ===
Contributors: newebpay
Tags: ecommerce, e-commerce, payment, Newebpay, neweb
Requires at least: 6.4
Tested up to: 6.6.1
Requires PHP: 8.0
Stable tag: 1.0.8
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

藍新科技金流外掛模組

== Description ==

藍新金流購物車外掛模組，提供合作特店與個人會員使用開放原始碼商店系統時，透過外掛套件快速完成界接藍新科技金流系統。


藍新金流整合各種金流工具，讓商店簡易、快速地串接使用。
不論代收模式(指由藍新撥款商店)，或閘道模式(指收單機構撥款商店)，皆能提供商店消費者多元支付方式，讓商店整合帳務輕鬆收款，針對各種支付場景彈性配置。
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
- 超商取貨付款
- 超商取貨不付款


= 注意事項 =
- 1.安裝此模組前，請先至藍新科技網站註冊會員，並申請相關收款服務，待服務審核通過後即可使用。


= 聯絡我們 =
  藍新金流客服信箱: cs@newebpay.com

== Installation ==

= 系統需求 =

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

V1.0.8
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