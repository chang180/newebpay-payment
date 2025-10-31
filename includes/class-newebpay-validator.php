<?php
/**
 * Newebpay 資料驗證類別
 * 提供統一的資料驗證和安全性檢查
 * 
 * @package NeWebPay_Payment
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Newebpay_Validator {
    
    /**
     * 驗證付款資料
     * 
     * @param array $data 付款資料
     * @return array 驗證後的資料
     * @throws InvalidArgumentException
     */
    public static function validate_payment_data( $data ) {
        if ( ! is_array( $data ) ) {
            throw new InvalidArgumentException( __( '付款資料必須為陣列格式', 'newebpay-payment' ) );
        }
        
        // 必要欄位檢查
        $required_fields = array(
            'MerchantID' => __( '商店代號', 'newebpay-payment' ),
            'Amt' => __( '金額', 'newebpay-payment' ),
            'ItemDesc' => __( '商品描述', 'newebpay-payment' )
        );
        
        foreach ( $required_fields as $field => $label ) {
            if ( empty( $data[ $field ] ) ) {
                throw new InvalidArgumentException( 
                    sprintf( __( '必要欄位 %s 不能為空', 'newebpay-payment' ), $label ) 
                );
            }
        }
        
        // 金額驗證
        if ( ! is_numeric( $data['Amt'] ) || $data['Amt'] <= 0 ) {
            throw new InvalidArgumentException( __( '金額必須為正數', 'newebpay-payment' ) );
        }
        
        // 金額範圍檢查（防止異常大額）
        if ( $data['Amt'] > 999999 ) {
            throw new InvalidArgumentException( __( '金額超出允許範圍', 'newebpay-payment' ) );
        }
        
        // 商品描述長度限制
        if ( mb_strlen( $data['ItemDesc'] ) > 50 ) {
            $data['ItemDesc'] = mb_substr( $data['ItemDesc'], 0, 50 );
        }
        
        // 清理和轉義資料
        $data = self::sanitize_payment_data( $data );
        
        return $data;
    }
    
    /**
     * 驗證回調資料
     * 
     * @param array $data 回調資料
     * @return array 驗證後的資料
     * @throws InvalidArgumentException
     */
    public static function validate_callback_data( $data ) {
        if ( ! is_array( $data ) ) {
            throw new InvalidArgumentException( __( '回調資料必須為陣列格式', 'newebpay-payment' ) );
        }
        
        // 必要欄位檢查
        $required_fields = array(
            'MerchantOrderNo' => __( '商店訂單編號', 'newebpay-payment' ),
            'Status' => __( '交易狀態', 'newebpay-payment' )
        );
        
        foreach ( $required_fields as $field => $label ) {
            if ( empty( $data[ $field ] ) ) {
                throw new InvalidArgumentException( 
                    sprintf( __( '必要欄位 %s 不能為空', 'newebpay-payment' ), $label ) 
                );
            }
        }
        
        // 狀態值驗證
        $valid_statuses = array( 'SUCCESS', 'CUSTOM', 'FAIL' );
        if ( ! in_array( $data['Status'], $valid_statuses ) ) {
            throw new InvalidArgumentException( __( '無效的交易狀態', 'newebpay-payment' ) );
        }
        
        return self::sanitize_callback_data( $data );
    }
    
    /**
     * 驗證付款方式選擇
     * 
     * @param string $payment_method 付款方式
     * @param array $available_methods 可用付款方式
     * @return string 驗證後的付款方式
     * @throws InvalidArgumentException
     */
    public static function validate_payment_method( $payment_method, $available_methods = array() ) {
        if ( empty( $payment_method ) ) {
            throw new InvalidArgumentException( __( '請選擇付款方式', 'newebpay-payment' ) );
        }
        
        // 清理輸入
        $payment_method = sanitize_text_field( $payment_method );
        
        // 檢查是否在可用列表中
        if ( ! empty( $available_methods ) && ! in_array( $payment_method, $available_methods ) ) {
            throw new InvalidArgumentException( __( '無效的付款方式', 'newebpay-payment' ) );
        }
        
        return $payment_method;
    }
    
    /**
     * 驗證訂單 ID
     * 
     * @param mixed $order_id 訂單 ID
     * @return int 驗證後的訂單 ID
     * @throws InvalidArgumentException
     */
    public static function validate_order_id( $order_id ) {
        $order_id = absint( $order_id );
        
        if ( $order_id <= 0 ) {
            throw new InvalidArgumentException( __( '無效的訂單 ID', 'newebpay-payment' ) );
        }
        
        return $order_id;
    }
    
    /**
     * 驗證金額
     * 
     * @param mixed $amount 金額
     * @return float 驗證後的金額
     * @throws InvalidArgumentException
     */
    public static function validate_amount( $amount ) {
        $amount = floatval( $amount );
        
        if ( $amount <= 0 ) {
            throw new InvalidArgumentException( __( '金額必須大於 0', 'newebpay-payment' ) );
        }
        
        if ( $amount > 999999 ) {
            throw new InvalidArgumentException( __( '金額超出允許範圍', 'newebpay-payment' ) );
        }
        
        return round( $amount, 2 );
    }
    
    /**
     * 清理付款資料
     * 
     * @param array $data 原始資料
     * @return array 清理後的資料
     */
    private static function sanitize_payment_data( $data ) {
        $sanitized = array();
        
        foreach ( $data as $key => $value ) {
            // 移除空值
            if ( $value === null || $value === '' ) {
                continue;
            }
            
            // 根據欄位類型進行清理
            switch ( $key ) {
                case 'MerchantID':
                case 'HashKey':
                case 'HashIV':
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
                    
                case 'Amt':
                    $sanitized[ $key ] = self::validate_amount( $value );
                    break;
                    
                case 'ItemDesc':
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
                    
                case 'Email':
                    $sanitized[ $key ] = sanitize_email( $value );
                    break;
                    
                default:
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * 清理回調資料
     * 
     * @param array $data 原始資料
     * @return array 清理後的資料
     */
    private static function sanitize_callback_data( $data ) {
        $sanitized = array();
        
        foreach ( $data as $key => $value ) {
            // 移除空值
            if ( $value === null || $value === '' ) {
                continue;
            }
            
            // 根據欄位類型進行清理
            switch ( $key ) {
                case 'MerchantOrderNo':
                case 'TradeNo':
                case 'Status':
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
                    
                case 'Amt':
                    $sanitized[ $key ] = self::validate_amount( $value );
                    break;
                    
                case 'Message':
                    $sanitized[ $key ] = sanitize_text_field( urldecode( $value ) );
                    break;
                    
                default:
                    $sanitized[ $key ] = sanitize_text_field( $value );
                    break;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * 驗證 IP 地址
     * 
     * @param string $ip IP 地址
     * @param array $allowed_ips 允許的 IP 列表
     * @return bool 是否允許
     */
    public static function validate_ip_address( $ip, $allowed_ips = array() ) {
        if ( empty( $allowed_ips ) ) {
            return true; // 如果沒有設定限制，則允許
        }
        
        return in_array( $ip, $allowed_ips );
    }
    
    /**
     * 驗證時間戳記
     * 
     * @param int $timestamp 時間戳記
     * @param int $tolerance 容許誤差（秒）
     * @return bool 是否有效
     */
    public static function validate_timestamp( $timestamp, $tolerance = 300 ) {
        $current_time = time();
        $diff = abs( $current_time - $timestamp );
        
        return $diff <= $tolerance;
    }
    
    /**
     * 驗證 SHA 簽章
     * 
     * @param string $data 原始資料
     * @param string $hash_key Hash Key
     * @param string $hash_iv Hash IV
     * @param string $provided_sha 提供的 SHA
     * @return bool 是否有效
     */
    public static function validate_sha_signature( $data, $hash_key, $hash_iv, $provided_sha ) {
        $expected_sha = self::generate_sha_signature( $data, $hash_key, $hash_iv );
        
        return hash_equals( $expected_sha, $provided_sha );
    }
    
    /**
     * 生成 SHA 簽章
     * 
     * @param string $data 原始資料
     * @param string $hash_key Hash Key
     * @param string $hash_iv Hash IV
     * @return string SHA 簽章
     */
    private static function generate_sha_signature( $data, $hash_key, $hash_iv ) {
        $check_string = "HashKey={$hash_key}&{$data}&HashIV={$hash_iv}";
        return strtoupper( hash( 'sha256', $check_string ) );
    }
}
