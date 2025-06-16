<?php
// includes/class-logger.php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Reviews_Exporter_Logger {
    private static $log_file_path;

    public static function init() {
        self::$log_file_path = self::get_log_file_path();
        // Ensure log directory exists
        if (!file_exists(dirname(self::$log_file_path))) {
            wp_mkdir_p(dirname(self::$log_file_path));
            // Add .htaccess to protect log directory
            $htaccess_content = "Deny from all\n";
            file_put_contents(dirname(self::$log_file_path) . '/.htaccess', $htaccess_content);
        }
    }

    public static function log($message, $level = 'info') {
        if (empty(self::$log_file_path)) {
            self::init();
        }
        
        $timestamp = current_time('mysql'); // Use WordPress time
        $log_line = sprintf("[%s] [%s] %s\n", $timestamp, strtoupper($level), $message);
        
        // Append to log file
        file_put_contents(self::$log_file_path, $log_line, FILE_APPEND | LOCK_EX);
    }

    public static function get_log_dir() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['basedir'] . '/wc-reviews-exports-logs/';
    }

    public static function get_log_file_path() {
        return self::get_log_dir() . 'wc-reviews-exporter.log';
    }

    public static function get_log_file_url() {
        $upload_dir = wp_upload_dir();
        return $upload_dir['baseurl'] . '/wc-reviews-exports-logs/wc-reviews-exporter.log';
    }

    public static function clear_logs() {
        $log_dir = self::get_log_dir();
        if (is_dir($log_dir)) {
            $files = glob($log_dir . '*.log');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
}
// Initialize logger early
WC_Reviews_Exporter_Logger::init();