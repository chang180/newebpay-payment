<?php
/**
 * Newebpay Logger 使用範例
 * 
 * 這個文件展示如何在 Newebpay Payment 插件中使用日誌記錄功能
 */

// 這個文件僅作為文檔用途，實際使用時請將程式碼整合到相應的類別中

/*
 * 基本使用方式：
 */

// 方法 1: 使用輔助函數（推薦）
newebpay_log('info', '支付處理開始', array('order_id' => 12345));
newebpay_log('error', '支付失敗', array('error_code' => 'E001', 'message' => 'Invalid parameters'));
newebpay_log('payment', '支付成功', array('order_id' => 12345, 'amount' => 1000));

// 方法 2: 直接使用 Logger 類別
$logger = Newebpay_Logger::get_instance();
$logger->info('一般訊息');
$logger->error('錯誤訊息');
$logger->warning('警告訊息');
$logger->debug('除錯訊息');
$logger->payment('支付相關訊息');
$logger->api('API 相關訊息');

/*
 * 在支付處理中的使用範例：
 */

// 在 nwpMPG.php 的 process_payment 方法中
public function process_payment($order_id) {
    newebpay_log('payment', 'Processing payment for order', array(
        'order_id' => $order_id,
        'payment_method' => $this->id
    ));
    
    try {
        global $woocommerce;
        $order = wc_get_order($order_id);
        
        // ... 支付處理邏輯 ...
        
        newebpay_log('payment', 'Payment processing completed successfully', array(
            'order_id' => $order_id,
            'redirect_url' => $order->get_checkout_payment_url(true)
        ));
        
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url(true),
        );
        
    } catch (Exception $e) {
        newebpay_log('error', 'Payment processing failed', array(
            'order_id' => $order_id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ));
        
        wc_add_notice('支付處理失敗，請稍後再試。', 'error');
        return array(
            'result' => 'failure'
        );
    }
}

/*
 * 在 API 調用中的使用範例：
 */

// 在發送 API 請求前
newebpay_log('api', 'Sending API request to Newebpay', array(
    'endpoint' => $this->gateway,
    'merchant_order_no' => $merchant_order_no,
    'amount' => $amount
));

// API 回應處理
if ($response_success) {
    newebpay_log('api', 'API response received successfully', array(
        'status' => $response_data['Status'],
        'trade_no' => $response_data['TradeNo']
    ));
} else {
    newebpay_log('api', 'API response failed', array(
        'error_code' => $response_data['Status'],
        'error_message' => $response_data['Message']
    ));
}

/*
 * 在錯誤處理中的使用範例：
 */

// 驗證失敗
if (!$this->chkShaIsVaildByReturnData($_REQUEST)) {
    newebpay_log('error', 'SHA validation failed', array(
        'request_data' => $_REQUEST,
        'expected_sha' => $expected_sha,
        'received_sha' => $_REQUEST['TradeSha']
    ));
    
    echo 'SHA vaild fail';
    exit;
}

/*
 * 在除錯模式中的使用範例：
 */

// 記錄詳細的除錯資訊
if (defined('WP_DEBUG') && WP_DEBUG) {
    newebpay_log('debug', 'Payment arguments prepared', array(
        'post_data' => $post_data,
        'encrypted_data' => $aes,
        'hash' => $sha256
    ));
}

/*
 * 管理功能使用範例：
 */

// 獲取所有 log 檔案
$logger = Newebpay_Logger::get_instance();
$log_files = $logger->get_log_files();

// 讀取最近的 log 記錄
$recent_logs = $logger->read_log('newebpay.log', 50);

// 清理所有 log 檔案
$logger->clear_logs();
?>
