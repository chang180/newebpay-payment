# Newebpay Blocks REST API 快速測試

以下為簡單的 curl 測試範例，用來驗證 REST API 是否回傳可用的付款方式。

將 `http://your-site.local` 換為您本機或測試環境的 URL。

```bash
# 取回 Newebpay Blocks 狀態
curl -sS "http://your-site.local/wp-json/newebpay/v1/status" | jq .

# 取回付款方式
curl -sS "http://your-site.local/wp-json/newebpay/v1/payment-methods" | jq .
```

備註：Windows PowerShell 使用者可以用 `Invoke-RestMethod` 來替代 curl：

```powershell
Invoke-RestMethod -Uri "http://your-site.local/wp-json/newebpay/v1/payment-methods" -Method GET
```
