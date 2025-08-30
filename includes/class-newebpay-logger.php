<?php
/**
 * Newebpay Payment Logger
 * 
 * 統一的日誌記錄管理類別
 * 
 * @package NewebPay
 * @since 1.0.9
 */

if (!defined('ABSPATH')) {
    exit;
}

class Newebpay_Logger {
    
    /**
     * 單例實例
     */
    private static $instance = null;
    
    /**
     * Log 目錄路徑
     */
    private $log_dir;
    
    /**
     * 是否啟用 logging
     */
    private $enabled;
    
    /**
     * 獲取單例實例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 建構子
     */
    private function __construct() {
        $this->log_dir = NEWEB_MAIN_PATH . '/logs/';
        
        // 只在 WP_DEBUG 或測試模式時啟用
        $this->enabled = defined('WP_DEBUG') && WP_DEBUG;
        
        // 確保 log 目錄存在
        if ($this->enabled && !is_dir($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
        
        // 創建 .htaccess 保護 log 目錄
        $this->protect_log_directory();
    }
    
    /**
     * 記錄一般訊息
     */
    public function info($message, $context = array()) {
        $this->log('INFO', $message, $context);
    }
    
    /**
     * 記錄錯誤訊息
     */
    public function error($message, $context = array()) {
        $this->log('ERROR', $message, $context);
    }
    
    /**
     * 記錄警告訊息
     */
    public function warning($message, $context = array()) {
        $this->log('WARNING', $message, $context);
    }
    
    /**
     * 記錄除錯訊息
     */
    public function debug($message, $context = array()) {
        $this->log('DEBUG', $message, $context);
    }
    
    /**
     * 記錄支付相關訊息
     */
    public function payment($message, $context = array()) {
        $this->log('PAYMENT', $message, $context, 'payment.log');
    }
    
    /**
     * 記錄 API 相關訊息
     */
    public function api($message, $context = array()) {
        $this->log('API', $message, $context, 'api.log');
    }
    
    /**
     * 核心 log 記錄方法
     */
    private function log($level, $message, $context = array(), $filename = 'newebpay.log') {
        if (!$this->enabled) {
            return;
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $formatted_message = $this->format_message($timestamp, $level, $message, $context);
        
        $log_file = $this->log_dir . $filename;
        
        // 寫入 log 文件
        error_log($formatted_message . PHP_EOL, 3, $log_file);
        
        // 檔案大小控制（超過 5MB 就輪替）
        $this->rotate_log_if_needed($log_file);
    }
    
    /**
     * 格式化 log 訊息
     */
    private function format_message($timestamp, $level, $message, $context) {
        $formatted = "[{$timestamp}] [{$level}] {$message}";
        
        if (!empty($context)) {
            $formatted .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        return $formatted;
    }
    
    /**
     * Log 檔案輪替
     */
    private function rotate_log_if_needed($log_file) {
        if (!file_exists($log_file)) {
            return;
        }
        
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (filesize($log_file) > $max_size) {
            $backup_file = $log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
            rename($log_file, $backup_file);
            
            // 清理舊的備份檔案（保留最近 5 個）
            $this->cleanup_old_backups(dirname($log_file));
        }
    }
    
    /**
     * 清理舊的備份檔案
     */
    private function cleanup_old_backups($dir) {
        $backup_files = glob($dir . '/*.bak');
        
        if (count($backup_files) > 5) {
            // 按修改時間排序
            usort($backup_files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // 刪除最舊的檔案
            $files_to_delete = array_slice($backup_files, 0, count($backup_files) - 5);
            foreach ($files_to_delete as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * 保護 log 目錄
     */
    private function protect_log_directory() {
        if (!$this->enabled) {
            return;
        }
        
        $htaccess_file = $this->log_dir . '.htaccess';
        
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Newebpay Payment Logs Protection\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "<Files ~ \"\\.(log|bak)$\">\n";
            $htaccess_content .= "    Order deny,allow\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // 創建 index.php 防止目錄列表
        $index_file = $this->log_dir . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, "<?php\n// Silence is golden.\n");
        }
    }
    
    /**
     * 清理所有 log 檔案
     */
    public function clear_logs() {
        if (!$this->enabled) {
            return false;
        }
        
        $files = glob($this->log_dir . '*.log');
        $backups = glob($this->log_dir . '*.bak');
        
        $all_files = array_merge($files, $backups);
        
        foreach ($all_files as $file) {
            unlink($file);
        }
        
        return true;
    }
    
    /**
     * 獲取 log 檔案列表
     */
    public function get_log_files() {
        if (!$this->enabled) {
            return array();
        }
        
        $files = glob($this->log_dir . '*.log');
        return array_map('basename', $files);
    }
    
    /**
     * 讀取 log 檔案內容
     */
    public function read_log($filename, $lines = 100) {
        if (!$this->enabled) {
            return '';
        }
        
        $log_file = $this->log_dir . $filename;
        
        if (!file_exists($log_file)) {
            return '';
        }
        
        // 讀取最後 N 行
        $file = new SplFileObject($log_file, 'r');
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();
        
        $start_line = max(0, $total_lines - $lines);
        $content = '';
        
        $file->seek($start_line);
        while (!$file->eof()) {
            $content .= $file->current();
            $file->next();
        }
        
        return $content;
    }
}

/**
 * 快速存取 logger 的輔助函數
 */
function newebpay_log($level, $message, $context = array()) {
    $logger = Newebpay_Logger::get_instance();
    
    switch (strtolower($level)) {
        case 'error':
            $logger->error($message, $context);
            break;
        case 'warning':
            $logger->warning($message, $context);
            break;
        case 'info':
            $logger->info($message, $context);
            break;
        case 'debug':
            $logger->debug($message, $context);
            break;
        case 'payment':
            $logger->payment($message, $context);
            break;
        case 'api':
            $logger->api($message, $context);
            break;
        default:
            $logger->info($message, $context);
    }
}
