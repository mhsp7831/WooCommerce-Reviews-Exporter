<?php
/**
 * Export Handler Class
 * مدیریت عملیات استخراج و تولید فایل CSV
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Reviews_Exporter_Export_Handler {
    
    private $selected_products = array();
    private $date_from = null;
    private $date_to = null;
    private $include_pending = false;
    
    public function __construct() {
        // افزایش حد زمان اجرا برای پردازش حجم بالا
        @set_time_limit(300); // 5 minutes
        @ini_set('memory_limit', '256M');
        WC_Reviews_Exporter_Logger::log('Export Handler initialized. Time limit set to 300s, Memory limit to 256M.', 'debug');
    }
    
    /**
     * پردازش درخواست استخراج
     */
    public function process_export() {
        WC_Reviews_Exporter_Logger::log('Starting CSV export process.', 'info');
        try {
            // اعتبارسنجی و دریافت داده‌های فرم
            WC_Reviews_Exporter_Logger::log('Validating and getting form data. POST data: ' . print_r($_POST, true), 'debug');
            $this->validate_and_get_form_data();
            WC_Reviews_Exporter_Logger::log(sprintf('Form data validated. Products: %s, Date From: %s, Date To: %s, Include Pending: %s', 
                                    implode(',', $this->selected_products), 
                                    $this->date_from ?: 'N/A', 
                                    $this->date_to ?: 'N/A', 
                                    $this->include_pending ? 'Yes' : 'No'), 'debug');
            
            // دریافت نظرات
            WC_Reviews_Exporter_Logger::log('Fetching reviews from database.', 'debug');
            $reviews = $this->get_reviews();
            WC_Reviews_Exporter_Logger::log(sprintf('Fetched %d reviews.', count($reviews)), 'info');
            
            // بررسی وجود نظر
            if (empty($reviews)) {
                $message = 'هیچ نظری برای محصولات و بازه زمانی انتخاب شده وجود ندارد.';
                WC_Reviews_Exporter_Logger::log($message, 'warning');
                $this->redirect_with_error($message);
                return;
            }
            
            // تولید فایل CSV
            WC_Reviews_Exporter_Logger::log('Generating CSV file.', 'debug');
            $filename = $this->generate_csv_file($reviews);
            WC_Reviews_Exporter_Logger::log(sprintf('CSV file "%s" generated successfully.', $filename), 'info');
            
            // ثبت لاگ
            $this->log_export_activity(count($reviews), $filename); // This already logs
            
            // ریدایرکت با پیام موفقیت
            $this->redirect_with_success($filename);
            
        } catch (Exception $e) {
            $error_message = 'خطا در پردازش: ' . $e->getMessage();
            WC_Reviews_Exporter_Logger::log($error_message, 'error');
            $this->redirect_with_error($error_message);
        }
    }
    
    /**
     * اعتبارسنجی و دریافت داده‌های فرم
     */
    private function validate_and_get_form_data() {
        // بررسی انتخاب محصولات
        if (empty($_POST['products']) || !is_array($_POST['products'])) {
            WC_Reviews_Exporter_Logger::log('No products selected or invalid product data.', 'warning');
            throw new Exception('لطفاً حداقل یک محصول را انتخاب کنید.');
        }
        
        // اعتبارسنجی محصولات
        $this->selected_products = array_map('intval', $_POST['products']);
        $this->selected_products = array_filter($this->selected_products, function($id) {
            return $id > 0 && get_post_type($id) === 'product';
        });
        
        if (empty($this->selected_products)) {
            WC_Reviews_Exporter_Logger::log('Selected products are not valid WooCommerce products after filtering.', 'warning');
            throw new Exception('محصولات انتخاب شده معتبر نیستند.');
        }
        
        // پردازش تاریخ‌ها
        if (!empty($_POST['date_from'])) {
            $input_date_from = sanitize_text_field($_POST['date_from']);
            WC_Reviews_Exporter_Logger::log('Attempting to parse "date_from": ' . $input_date_from, 'debug');
            $this->date_from = $this->parse_and_convert_date($input_date_from);
            if (!$this->date_from) {
                WC_Reviews_Exporter_Logger::log('Invalid date format for "date_from": ' . $input_date_from, 'error');
                throw new Exception('فرمت تاریخ شروع نامعتبر است. لطفاً تاریخ را به فرمت شمسی (مثال: ۱۴۰۳/۰۳/۱۵) یا میلادی (مثال: 2024/06/15) وارد کنید.');
            }
            WC_Reviews_Exporter_Logger::log('Parsed "date_from" to Gregorian: ' . $this->date_from, 'debug');
        }
        
        if (!empty($_POST['date_to'])) {
            $input_date_to = sanitize_text_field($_POST['date_to']);
            WC_Reviews_Exporter_Logger::log('Attempting to parse "date_to": ' . $input_date_to, 'debug');
            $this->date_to = $this->parse_and_convert_date($input_date_to);
            if (!$this->date_to) {
                WC_Reviews_Exporter_Logger::log('Invalid date format for "date_to": ' . $input_date_to, 'error');
                throw new Exception('فرمت تاریخ پایان نامعتبر است. لطفاً تاریخ را به فرمت شمسی (مثال: ۱۴۰۳/۰۳/۱۵) یا میلادی (مثال: 2024/06/15) وارد کنید.');
            }
            WC_Reviews_Exporter_Logger::log('Parsed "date_to" to Gregorian: ' . $this->date_to, 'debug');
        }
        
        // بررسی منطقی تاریخ‌ها
        if ($this->date_from && $this->date_to && $this->date_from > $this->date_to) {
            WC_Reviews_Exporter_Logger::log(sprintf('Date from (%s) is after date to (%s).', $this->date_from, $this->date_to), 'error');
            throw new Exception('تاریخ شروع نمی‌تواند بعد از تاریخ پایان باشد.');
        }
        
        // نظرات در انتظار تأیید
        $this->include_pending = !empty($_POST['include_pending']);
    }

    /**
     * تشخیص و تبدیل تاریخ ورودی (شمسی یا میلادی) به فرمت میلادی 'YYYY-MM-DD'.
     * @param string $date_string تاریخ وارد شده توسط کاربر.
     * @return string|null تاریخ میلادی به فرمت 'YYYY-MM-DD' یا null در صورت نامعتبر بودن.
     */
    private function parse_and_convert_date($date_string) {
        if (empty($date_string)) {
            return null;
        }
        WC_Reviews_Exporter_Logger::log('Attempting to convert date string: "' . $date_string . '"', 'debug');

        // Step 1: Try as Persian (Jalali) date. Handles both Persian and English digits.
        $gregorian_from_persian = WC_Reviews_Exporter_Persian_Date::persian_to_gregorian($date_string);
        
        if ($gregorian_from_persian) {
            WC_Reviews_Exporter_Logger::log('Successfully converted from Persian (Jalali): ' . $gregorian_from_persian, 'debug');
            return $gregorian_from_persian;
        }
        WC_Reviews_Exporter_Logger::log('Could not convert as Persian (Jalali) date.', 'debug');

        // Step 2: If not Persian, try as Gregorian date (using english digits for strtotime).
        $date_string_english_digits = WC_Reviews_Exporter_Persian_Date::english_numbers($date_string);
        
        // Normalize common date separators for strtotime
        $normalized_date_string = str_replace(['/', '.'], '-', $date_string_english_digits);
        WC_Reviews_Exporter_Logger::log('Attempting strtotime on normalized English digits: "' . $normalized_date_string . '"', 'debug');

        $timestamp = strtotime($normalized_date_string);
        
        if ($timestamp !== false) {
            $converted_date = date('Y-m-d', $timestamp);
            // Basic validation to avoid false positives like strtotime('0') returning '1970-01-01'
            if ($converted_date !== '1970-01-01' || $normalized_date_string === '1970-01-01' || $normalized_date_string === '0') {
                 // Additional check: Ensure the year is somewhat reasonable (e.g., after 1900)
                if (date('Y', $timestamp) >= 1900 && date('Y', $timestamp) <= (int)date('Y') + 10) { // Current year + 10 years for future dates
                    WC_Reviews_Exporter_Logger::log('Successfully converted from Gregorian: ' . $converted_date, 'debug');
                    return $converted_date;
                }
            }
        }
        WC_Reviews_Exporter_Logger::log('Could not convert as Gregorian date.', 'debug');
        
        return null; // If neither format was valid
    }
    
    /**
     * دریافت نظرات بر اساس فیلترها
     */
    private function get_reviews() {
        global $wpdb;
        
        // ساخت WHERE clause
        $where_conditions = array();
        $where_values = array();
        
        // فیلتر محصولات
        $products_placeholder = implode(',', array_fill(0, count($this->selected_products), '%d'));
        $where_conditions[] = "c.comment_post_ID IN ($products_placeholder)";
        $where_values = array_merge($where_values, $this->selected_products);
        
        // فیلتر تاریخ شروع
        if ($this->date_from) {
            $where_conditions[] = "c.comment_date >= %s";
            $where_values[] = $this->date_from . ' 00:00:00'; // شامل کل روز
        }
        
        // فیلتر تاریخ پایان
        if ($this->date_to) {
            $where_conditions[] = "c.comment_date <= %s";
            $where_values[] = $this->date_to . ' 23:59:59'; // شامل کل روز
        }
        
        // فیلتر وضعیت نظرات
        if (!$this->include_pending) {
            $where_conditions[] = "c.comment_approved = '1'";
        } else {
            $where_conditions[] = "c.comment_approved IN ('1', '0')";
        }
        
        // کوئری اصلی
        $query = "
            SELECT 
                c.comment_ID,
                c.comment_author,
                c.comment_author_email,
                c.comment_content,
                c.comment_date,
                c.comment_approved,
                c.comment_author_IP,
                p.post_title as product_name,
                COALESCE(cm.meta_value, '0') as rating
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
            LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = 'rating'
            WHERE c.comment_type = 'review'
            AND " . implode(' AND ', $where_conditions) . "
            ORDER BY c.comment_date DESC
        ";
        
        WC_Reviews_Exporter_Logger::log('Executing SQL query: ' . $wpdb->prepare($query, $where_values), 'debug');
        return $wpdb->get_results($wpdb->prepare($query, $where_values));
    }
    
    /**
     * تولید فایل CSV
     */
    private function generate_csv_file($reviews) {
        // ایجاد نام فایل با تاریخ فارسی
        $filename = $this->generate_filename();
        $csv_dir = wc_reviews_exporter_get_csv_dir();
        $file_path = $csv_dir . $filename;
        
        // باز کردن فایل برای نوشتن
        $file_handle = fopen($file_path, 'w');
        if (!$file_handle) {
            WC_Reviews_Exporter_Logger::log('Failed to open CSV file for writing: ' . $file_path, 'critical');
            throw new Exception('خطا در ایجاد فایل CSV.');
        }
        WC_Reviews_Exporter_Logger::log('CSV file handle opened: ' . $file_path, 'debug');
        
        // اضافه کردن BOM برای پشتیبانی از UTF-8 در Excel
        fwrite($file_handle, "\xEF\xBB\xBF");
        
        // هدرهای CSV
        $headers = array(
            'شناسه نظر',
            'نام کاربر',
            'ایمیل',
            'محتوای نظر',
            'امتیاز',
            'تاریخ',
            'وضعیت',
            'نام محصول',
            'IP کاربر'
        );
        
        // نوشتن هدرها
        fputcsv($file_handle, $headers);
        WC_Reviews_Exporter_Logger::log('CSV headers written.', 'debug');
        
        // استفاده از Batch Processing برای حجم بالا
        if (count($reviews) > 500) {
            WC_Reviews_Exporter_Logger::log('Processing reviews in batches (total: ' . count($reviews) . ').', 'debug');
            $this->process_reviews_in_batches($file_handle, $reviews);
        } else {
            WC_Reviews_Exporter_Logger::log('Processing reviews simply (total: ' . count($reviews) . ').', 'debug');
            $this->process_reviews_simple($file_handle, $reviews);
        }
        
        fclose($file_handle);
        WC_Reviews_Exporter_Logger::log('CSV file handle closed.', 'debug');
        
        return $filename;
    }
    
    /**
     * پردازش نظرات به صورت Batch
     */
    private function process_reviews_in_batches($file_handle, $reviews) {
        $batch_size = 100;
        $total_reviews = count($reviews);
        $processed = 0;
        
        for ($i = 0; $i < $total_reviews; $i += $batch_size) {
            $batch = array_slice($reviews, $i, $batch_size);
            WC_Reviews_Exporter_Logger::log(sprintf('Processing batch %d (offset %d, size %d).', ($i / $batch_size) + 1, $i, count($batch)), 'debug');
            
            foreach ($batch as $review) {
                $row = $this->prepare_csv_row($review);
                fputcsv($file_handle, $row);
                $processed++;
            }
            
            // آزادسازی حافظه
            unset($batch);
            
            // اجازه به سرور برای پردازش
            if (($i + $batch_size) < $total_reviews) {
                usleep(50000); // 0.05 second pause
            }
        }
        WC_Reviews_Exporter_Logger::log(sprintf('Finished batch processing. Total %d reviews processed.', $processed), 'debug');
    }
    
    /**
     * پردازش ساده نظرات
     */
    private function process_reviews_simple($file_handle, $reviews) {
        foreach ($reviews as $review) {
            $row = $this->prepare_csv_row($review);
            fputcsv($file_handle, $row);
        }
        WC_Reviews_Exporter_Logger::log(sprintf('Finished simple processing. Total %d reviews processed.', count($reviews)), 'debug');
    }
    
    /**
     * آماده‌سازی سطر CSV
     */
    private function prepare_csv_row($review) {
        // تبدیل تاریخ میلادی به شمسی
        $persian_date = WC_Reviews_Exporter_Persian_Date::format_date($review->comment_date);
        
        // تعیین وضعیت نظر
        $status = $review->comment_approved == '1' ? 'تأیید شده' : 'در انتظار تأیید';
        
        // آماده‌سازی محتوای نظر (حذف HTML tags)
        $content = strip_tags($review->comment_content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        return array(
            $review->comment_ID,
            $review->comment_author,
            $review->comment_author_email,
            $content,
            $review->rating,
            $persian_date,
            $status,
            $review->product_name,
            $review->comment_author_IP
        );
    }
    
    /**
     * تولید نام فایل
     */
    private function generate_filename() {
        $date_part = '';
        
        if ($this->date_from && $this->date_to) {
            $from_persian = WC_Reviews_Exporter_Persian_Date::format_date($this->date_from, 'Y-m-d');
            $to_persian = WC_Reviews_Exporter_Persian_Date::format_date($this->date_to, 'Y-m-d');
            // تبدیل اعداد فارسی به انگلیسی
            $from_persian = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $from_persian);
            $to_persian = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $to_persian);
            $date_part = '_' . $from_persian . '_' . $to_persian;
        } elseif ($this->date_from) {
            $from_persian = WC_Reviews_Exporter_Persian_Date::format_date($this->date_from, 'Y-m-d');
            // تبدیل اعداد فارسی به انگلیسی
            $from_persian = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $from_persian);
            $date_part = '_from_' . $from_persian;
        } elseif ($this->date_to) {
            $to_persian = WC_Reviews_Exporter_Persian_Date::format_date($this->date_to, 'Y-m-d');
            // تبدیل اعداد فارسی به انگلیسی
            $to_persian = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $to_persian);
            $date_part = '_to_' . $to_persian;
        } else {
            $date_part = '_all-time';
        }
        
        $timestamp_gregorian = current_time('mysql');
        $timestamp_persian = WC_Reviews_Exporter_Persian_Date::format_date($timestamp_gregorian, 'YmdHis');
        // تبدیل اعداد فارسی به انگلیسی
        $timestamp_persian = str_replace(['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'], ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], $timestamp_persian);
        
        $filename = 'reviews' . $date_part . '_' . $timestamp_persian . '.csv';
        WC_Reviews_Exporter_Logger::log('Generated CSV filename: ' . $filename, 'debug');
        return $filename;
    }
    
    /**
     * ثبت فعالیت استخراج
     */
    private function log_export_activity($review_count, $filename) {
        $current_user = wp_get_current_user();
        $log_message = sprintf(
            'WC Reviews Export: کاربر %s (شناسه: %d) تعداد %d نظر را در فایل %s استخراج کرد.',
            $current_user->user_login,
            $current_user->ID,
            $review_count,
            $filename
        );
        WC_Reviews_Exporter_Logger::log($log_message, 'info');
    }
    
    /**
     * ریدایرکت با پیام موفقیت
     */
    private function redirect_with_success($filename) {
        $redirect_url = add_query_arg(array(
            'export_success' => '1',
            'filename' => urlencode($filename)
        ), admin_url('edit.php?post_type=product&page=wc-reviews-exporter'));
        
        WC_Reviews_Exporter_Logger::log('Export successful. Redirecting to: ' . $redirect_url, 'info');
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * ریدایرکت با پیام خطا
     */
    private function redirect_with_error($error_message) {
        $redirect_url = add_query_arg(array(
            'export_error' => urlencode($error_message)
        ), admin_url('edit.php?post_type=product&page=wc-reviews-exporter'));
        
        WC_Reviews_Exporter_Logger::log('Export failed. Redirecting to: ' . $redirect_url . ' with error: ' . $error_message, 'error');
        wp_redirect($redirect_url);
        exit;
    }
}