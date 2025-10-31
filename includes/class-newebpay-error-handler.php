<?php
/**
 * Newebpay 錯誤處理類別
 * 提供統一的錯誤處理和記錄機制
 * 
 * @package NeWebPay_Payment
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_Error_Handler {
    
    /**
     * 錯誤代碼常數
     */
    const ERROR_INVALID_DATA = 'invalid_data';
    const ERROR_PAYMENT_FAILED = 'payment_failed';
    const ERROR_CALLBACK_FAILED = 'callback_failed';
    const ERROR_SECURITY_VIOLATION = 'security_violation';
    const ERROR_SYSTEM_ERROR = 'system_error';
    
    /**
     * 錯誤嚴重程度
     */
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';
    
    /**
     * 處理錯誤
     * 
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     * @param array $context 錯誤上下文
     * @param string $severity 嚴重程度
     */
    public static function handle_error( $message, $code = self::ERROR_SYSTEM_ERROR, $context = array(), $severity = self::SEVERITY_MEDIUM ) {
        // 記錄錯誤到日誌
        self::log_error( $message, $code, $context, $severity );
        
        // 根據錯誤類型決定處理方式
        switch ( $severity ) {
            case self::SEVERITY_CRITICAL:
                self::handle_critical_error( $message, $code );
                break;
                
            case self::SEVERITY_HIGH:
                self::handle_high_severity_error( $message, $code );
                break;
                
            case self::SEVERITY_MEDIUM:
                self::handle_medium_severity_error( $message, $code );
                break;
                
            case self::SEVERITY_LOW:
                self::handle_low_severity_error( $message, $code );
                break;
        }
    }
    
    /**
     * 處理付款錯誤
     * 
     * @param string $message 錯誤訊息
     * @param array $context 錯誤上下文
     */
    public static function handle_payment_error( $message, $context = array() ) {
        self::handle_error( $message, self::ERROR_PAYMENT_FAILED, $context, self::SEVERITY_HIGH );
    }
    
    /**
     * 處理回調錯誤
     * 
     * @param string $message 錯誤訊息
     * @param array $context 錯誤上下文
     */
    public static function handle_callback_error( $message, $context = array() ) {
        self::handle_error( $message, self::ERROR_CALLBACK_FAILED, $context, self::SEVERITY_HIGH );
    }
    
    /**
     * 處理安全性錯誤
     * 
     * @param string $message 錯誤訊息
     * @param array $context 錯誤上下文
     */
    public static function handle_security_error( $message, $context = array() ) {
        self::handle_error( $message, self::ERROR_SECURITY_VIOLATION, $context, self::SEVERITY_CRITICAL );
    }
    
    /**
     * 處理資料驗證錯誤
     * 
     * @param string $message 錯誤訊息
     * @param array $context 錯誤上下文
     */
    public static function handle_validation_error( $message, $context = array() ) {
        self::handle_error( $message, self::ERROR_INVALID_DATA, $context, self::SEVERITY_MEDIUM );
    }
    
    /**
     * 記錄錯誤到日誌
     * 
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     * @param array $context 錯誤上下文
     * @param string $severity 嚴重程度
     */
    private static function log_error( $message, $code, $context, $severity ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            
            $log_message = sprintf( 
                '[%s] %s: %s', 
                $severity, 
                $code, 
                $message 
            );
            
            $logger->error( $log_message, array(
                'source' => 'newebpay-payment',
                'context' => $context,
                'severity' => $severity,
                'code' => $code
            ) );
        }
    }
    
    /**
     * 處理嚴重錯誤
     * 
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     */
    private static function handle_critical_error( $message, $code ) {
        // 發送管理員通知
        self::send_admin_notification( $message, $code );
        
        // 如果是 AJAX 請求，返回 JSON 錯誤
        if ( wp_doing_ajax() ) {
            wp_send_json_error( array(
                'message' => __( '系統發生嚴重錯誤，請聯繫管理員', 'newebpay-payment' ),
                'code' => $code
            ), 500 );
        } else {
            wp_die( 
                __( '系統發生嚴重錯誤，請聯繫管理員', 'newebpay-payment' ), 
                __( '系統錯誤', 'newebpay-payment' ), 
                array( 'response' => 500 ) 
            );
        }
    }
    
    /**
     * 處理高嚴重程度錯誤
     * 
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     */
    private static function handle_high_severity_error( $message, $code ) {
        // 如果是 AJAX 請求，返回 JSON 錯誤
        if ( wp_doing_ajax() ) {
            wp_send_json_error( array(
                'message' => $message,
                'code' => $code
            ), 400 );
        } else {
            wp_die( $message, __( '錯誤', 'newebpay-payment' ), array( 'response' => 400 ) );
        }
    }
    
    /**
     * 處理中等嚴重程度錯誤
     * 
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     */
    private static function handle_medium_severity_error( $message, $code ) {
        // 如果是 AJAX 請求，返回 JSON 錯誤
        if ( wp_doing_ajax() ) {
            wp_send_json_error( array(
                'message' => $message,
                'code' => $code
            ), 400 );
        } else {
            // 顯示錯誤訊息但不中斷執行
            if ( function_exists( 'wc_add_notice' ) ) {
                wc_add_notice( $message, 'error' );
            }
        }
    }
    
    /**
     * 處理低嚴重程度錯誤
     * 
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     */
    private static function handle_low_severity_error( $message, $code ) {
        // 只記錄到日誌，不中斷執行
        // 可以選擇性地顯示警告訊息
        if ( function_exists( 'wc_add_notice' ) ) {
            wc_add_notice( $message, 'notice' );
        }
    }
    
    /**
     * 發送管理員通知
     * 
     * @param string $message 錯誤訊息
     * @param string $code 錯誤代碼
     */
    private static function send_admin_notification( $message, $code ) {
        $admin_email = get_option( 'admin_email' );
        if ( ! $admin_email ) {
            return;
        }
        
        $subject = sprintf( 
            __( '[%s] 藍新金流插件嚴重錯誤', 'newebpay-payment' ), 
            get_bloginfo( 'name' ) 
        );
        
        $body = sprintf( 
            __( '插件發生嚴重錯誤：%s\n錯誤代碼：%s\n時間：%s', 'newebpay-payment' ),
            $message,
            $code,
            current_time( 'Y-m-d H:i:s' )
        );
        
        wp_mail( $admin_email, $subject, $body );
    }
    
    /**
     * 驗證錯誤處理
     * 
     * @param Exception $exception 例外物件
     * @param array $context 錯誤上下文
     */
    public static function handle_exception( $exception, $context = array() ) {
        $message = $exception->getMessage();
        $code = $exception->getCode() ?: self::ERROR_SYSTEM_ERROR;
        
        // 根據例外類型決定嚴重程度
        $severity = self::SEVERITY_MEDIUM;
        if ( $exception instanceof InvalidArgumentException ) {
            $severity = self::SEVERITY_LOW;
        } elseif ( $exception instanceof Exception && strpos( $exception->getMessage(), 'security' ) !== false ) {
            $severity = self::SEVERITY_CRITICAL;
        }
        
        self::handle_error( $message, $code, $context, $severity );
    }
    
    /**
     * 取得使用者友善的錯誤訊息
     * 
     * @param string $code 錯誤代碼
     * @return string 使用者友善的錯誤訊息
     */
    public static function get_user_friendly_message( $code ) {
        $messages = array(
            self::ERROR_INVALID_DATA => __( '資料格式不正確，請重新填寫', 'newebpay-payment' ),
            self::ERROR_PAYMENT_FAILED => __( '付款處理失敗，請重試或選擇其他付款方式', 'newebpay-payment' ),
            self::ERROR_CALLBACK_FAILED => __( '付款確認失敗，請聯繫客服', 'newebpay-payment' ),
            self::ERROR_SECURITY_VIOLATION => __( '安全性驗證失敗，請重新操作', 'newebpay-payment' ),
            self::ERROR_SYSTEM_ERROR => __( '系統暫時無法處理，請稍後再試', 'newebpay-payment' )
        );
        
        return isset( $messages[ $code ] ) ? $messages[ $code ] : __( '發生未知錯誤', 'newebpay-payment' );
    }
}
