<?php
/**
 * Persian Date Class
 * کلاس مدیریت تاریخ فارسی
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure jDateTime class is available
require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'includes/jdatetime.class.php';
require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'includes/jdate.php';

class WC_Reviews_Exporter_Persian_Date {
    
    /**
     * تبدیل تاریخ میلادی به شمسی با فرمت دلخواه
     */
    public static function format_date($date, $format = 'Y/m/d H:i:s') {
        if (empty($date)) {
            return '';
        }
        
        // تبدیل به timestamp
        if (is_string($date)) {
            $timestamp = strtotime($date);
        } else {
            $timestamp = $date;
        }
        
        if (!$timestamp) {
            return ''; // در صورت نامعتبر بودن تاریخ، رشته خالی برگردانده شود
        }
        
        // استفاده از jDate برای تبدیل به شمسی
        return \Miladr\Jalali\jDate::forge($timestamp)->format($format);
    }
    
    /**
     * تبدیل اعداد انگلیسی (0-9) به فارسی (۰-۹) در یک رشته
     */
    public static function persian_numbers($string) {
        $persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        
        return str_replace($english_digits, $persian_digits, $string);
    }
    
    /**
     * تبدیل اعداد فارسی (۰-۹) به انگلیسی (0-9) در یک رشته
     */
    public static function english_numbers($string) {
        $persian_digits = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
        $english_digits = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        
        return str_replace($persian_digits, $english_digits, $string);
    }
    
    /**
     * تبدیل تاریخ شمسی (مانند 1403/03/15) به تاریخ میلادی (YYYY-MM-DD)
     */
    public static function persian_to_gregorian($persian_date) {
        if (empty($persian_date)) {
            return null;
        }
        
        // تبدیل اعداد فارسی به انگلیسی قبل از تجزیه
        $persian_date = self::english_numbers($persian_date);
        
        // تجزیه تاریخ شمسی (سال/ماه/روز)
        $date_parts = explode('/', $persian_date);
        if (count($date_parts) !== 3) {
            return null; // فرمت نامعتبر
        }
        
        $year = intval($date_parts[0]);
        $month = intval($date_parts[1]);
        $day = intval($date_parts[2]);
        
        // اعتبارسنجی اولیه محدوده
        // این اعتبارسنجی ممکن است با برخی تاریخ‌های میلادی که به عنوان ورودی شمسی وارد شده‌اند تداخل داشته باشد
        // به همین دلیل منطق تشخیص در Export_Handler::parse_and_convert_date() اضافه شد.
        if ($year < 1300 || $year > 1500 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }
        
        // استفاده از jmktime برای تبدیل به timestamp میلادی
        $gregorian_timestamp = \jDateTime::mktime(0, 0, 0, $month, $day, $year);
        
        if (!$gregorian_timestamp || $gregorian_timestamp === false) { // Handle potential false from jmktime
            return null;
        }

        // یک بررسی اضافی برای صحت تبدیل: اگر timestamp خیلی غیرمنطقی بود
        // مثلاً تاریخ‌های خارج از محدوده strtotime که به 1970 برمی‌گردند.
        $converted_date_str = date('Y-m-d', $gregorian_timestamp);
        if ($converted_date_str == '1970-01-01' && ($year != 1348 || $month != 10 || $day != 11)) { // 1348/10/11 شمسی == 1970-01-01 میلادی
             return null;
        }

        return $converted_date_str;
    }
    
    /**
     * دریافت تاریخ امروز به فرمت شمسی دلخواه
     */
    public static function today($format = 'Y/m/d') {
        return \Miladr\Jalali\jDate::forge()->format($format);
    }
    
    /**
     * دریافت نام ماه فارسی
     */
    public static function persian_month_name($month_number) {
        $months = array(
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        );
        
        return isset($months[$month_number]) ? $months[$month_number] : '';
    }
}