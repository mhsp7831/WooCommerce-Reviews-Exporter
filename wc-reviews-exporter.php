<?php
/**
 * Plugin Name: صادرکننده نظرات WooCommerce
 * Description: استخراج نظرات محصولات WooCommerce به فرمت CSV از طریق پنل ادمین
 * Version: 1.0.0
 * Author: MHSP :)
 * Author URI: http://github.com/mhsp7831
 * Text Domain: wc-reviews-exporter
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_REVIEWS_EXPORTER_VERSION', '1.0.0');
define('WC_REVIEWS_EXPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_REVIEWS_EXPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_REVIEWS_EXPORTER_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class WC_Reviews_Exporter {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // بررسی وجود WooCommerce
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin files
        $this->load_files();
        
        // Initialize admin menu
        if (is_admin()) {
            new WC_Reviews_Exporter_Admin_Menu();
        }
    }
    
    /**
     * Load required files
     */
    private function load_files() {
        // Load logger first to ensure it's available for other classes
        require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'includes/class-logger.php';
        
        // Log plugin initialization
        WC_Reviews_Exporter_Logger::log('WC Reviews Exporter: Initializing plugin.', 'debug');

        // Load jdate library
        require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'includes/jdate.php';
        
        // Load plugin classes
        require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'includes/class-persian-date.php';
        require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'includes/class-csv-generator.php';
        require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'includes/class-batch-processor.php';
        require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'admin/class-admin-menu.php';
        require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'admin/class-export-handler.php';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create uploads directory for CSV files
        $upload_dir = wp_upload_dir();
        $csv_dir = $upload_dir['basedir'] . '/wc-reviews-exports/';
        
        if (!file_exists($csv_dir)) {
            wp_mkdir_p($csv_dir);
            // Create .htaccess to protect directory
            $htaccess_content = "Options -Indexes\n<Files *.csv>\nForceType application/octet-stream\nHeader set Content-Disposition attachment\n</Files>";
            file_put_contents($csv_dir . '.htaccess', $htaccess_content);
        }

        // Initialize logger (if not already init) and log activation
        if (class_exists('WC_Reviews_Exporter_Logger')) {
            WC_Reviews_Exporter_Logger::init(); // Ensure log path is set
            WC_Reviews_Exporter_Logger::log('WC Reviews Exporter: Plugin activated.', 'info');
        } else {
            error_log('WC Reviews Exporter: Plugin activated (Logger not initialized yet).');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up old CSV files (older than 24 hours)
        $this->cleanup_old_files();
        
        // Clear all log files on deactivation
        if (class_exists('WC_Reviews_Exporter_Logger')) {
            WC_Reviews_Exporter_Logger::log('WC Reviews Exporter: Plugin deactivating. Clearing logs...', 'info');
            WC_Reviews_Exporter_Logger::clear_logs();
        } else {
            error_log('WC Reviews Exporter: Plugin deactivating (Logger not initialized yet).');
        }
    }
    
    /**
     * Show notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>WooCommerce Reviews Exporter:</strong> ';
        echo 'این افزونه نیاز به فعال بودن افزونه WooCommerce دارد.';
        echo '</p></div>';
    }
    
    /**
     * Clean up old CSV files
     */
    private function cleanup_old_files() {
        $upload_dir = wp_upload_dir();
        $csv_dir = $upload_dir['basedir'] . '/wc-reviews-exports/';
        
        if (is_dir($csv_dir)) {
            $files = glob($csv_dir . '*.csv');
            $now = time();
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    $file_time = filemtime($file);
                    // Delete files older than 24 hours
                    if (($now - $file_time) > (24 * 3600)) {
                        unlink($file);
                        WC_Reviews_Exporter_Logger::log(sprintf('CSV file deleted during cleanup: %s', basename($file)), 'debug');
                    }
                }
            }
        }
    }
}

// Initialize plugin
function wc_reviews_exporter_init() {
    return WC_Reviews_Exporter::get_instance();
}

// Start the plugin
wc_reviews_exporter_init();

/**
 * Helper function to get upload directory for CSV files
 */
function wc_reviews_exporter_get_csv_dir() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['basedir'] . '/wc-reviews-exports/';
}

/**
 * Helper function to get CSV URL
 */
function wc_reviews_exporter_get_csv_url() {
    $upload_dir = wp_upload_dir();
    return $upload_dir['baseurl'] . '/wc-reviews-exports/';
}