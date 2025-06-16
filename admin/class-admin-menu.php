<?php
/**
 * Admin Menu Class
 * مدیریت منوی ادمین و نمایش صفحه استخراج
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Reviews_Exporter_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_export_request'));
    }
    
    /**
     * اضافه کردن زیرمنو به بخش "محصولات" ووکامرس در پنل ادمین
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=product',
            'استخراج نظرات',
            'استخراج نظرات',
            'manage_options',
            'wc-reviews-exporter',
            array($this, 'display_export_page')
        );
    }
    
    /**
     * بارگذاری فایل‌های CSS و JS مورد نیاز برای صفحه افزونه.
     * DatePicker حذف شده و تنها اسکریپت‌ها و استایل‌های اصلی افزونه بارگذاری می‌شوند.
     */
    public function enqueue_admin_scripts($hook) {
        // اطمینان از بارگذاری اسکریپت‌ها فقط در صفحه افزونه ما
        if ($hook !== 'product_page_wc-reviews-exporter') {
            return;
        }
        
        // بارگذاری استایل‌های سفارشی افزونه
        wp_enqueue_style(
            'wc-reviews-exporter-admin-style',
            WC_REVIEWS_EXPORTER_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            WC_REVIEWS_EXPORTER_VERSION
        );
        
        // بارگذاری اسکریپت‌های سفارشی افزونه
        wp_enqueue_script(
            'wc-reviews-exporter-admin-script',
            WC_REVIEWS_EXPORTER_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'), // فقط jQuery به عنوان وابستگی باقی می‌ماند
            WC_REVIEWS_EXPORTER_VERSION,
            true // بارگذاری در فوتر
        );
    }
    
    /**
     * نمایش محتوای صفحه استخراج نظرات در پنل ادمین
     */
    public function display_export_page() {
        // بررسی دسترسی کاربر
        if (!current_user_can('manage_options')) {
            wp_die('شما دسترسی لازم برای مشاهده این صفحه را ندارید.');
        }
        
        // دریافت لیست محصولات همراه با تعداد نظراتشان
        $products = $this->get_products_with_reviews();
        
        // نمایش فایل ویو (HTML) فرم استخراج
        include WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'admin/views/export-page.php';
    }
    
    /**
     * دریافت لیست محصولات WooCommerce همراه با تعداد کل نظرات و نظرات در انتظار تأیید
     */
    private function get_products_with_reviews() {
        global $wpdb;
        
        $query = "
            SELECT 
                p.ID, 
                p.post_title, 
                COUNT(c.comment_ID) as total_review_count,
                SUM(CASE WHEN c.comment_approved = '0' THEN 1 ELSE 0 END) as pending_review_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->comments} c ON p.ID = c.comment_post_ID AND c.comment_type = 'review'
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish'
            GROUP BY p.ID, p.post_title
            ORDER BY p.post_title ASC
        ";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * مدیریت درخواست سابمیت فرم استخراج
     */
    public function handle_export_request() {
        if (!isset($_POST['export_reviews']) || !isset($_POST['wc_reviews_export_nonce'])) {
            return;
        }
        
        // بررسی Nonce برای امنیت (جلوگیری از حملات CSRF)
        if (!wp_verify_nonce($_POST['wc_reviews_export_nonce'], 'wc_reviews_export')) {
            WC_Reviews_Exporter_Logger::log('Nonce verification failed for export request.', 'error');
            wp_die('خطای امنیتی. لطفاً دوباره تلاش کنید.');
        }
        
        // بررسی سطح دسترسی کاربر
        if (!current_user_can('manage_options')) {
            WC_Reviews_Exporter_Logger::log(sprintf('Unauthorized access attempt to export reviews by user ID: %d', get_current_user_id()), 'warning');
            wp_die('شما دسترسی لازم را ندارید.');
        }
        
        // ایجاد نمونه از کلاس Export_Handler و پردازش درخواست
        $export_handler = new WC_Reviews_Exporter_Export_Handler();
        $export_handler->process_export();
    }
}

/**
 * تابع کمکی برای تبدیل اعداد انگلیسی به فارسی
 * این تابع به عنوان یک Helper Function در فایل View نیز استفاده می‌شود.
 */
if (!function_exists('wc_reviews_exporter_persian_number')) {
    function wc_reviews_exporter_persian_number($number) {
        $persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        
        return str_replace($english_digits, $persian_digits, $number);
    }
}