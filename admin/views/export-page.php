<?php
/**
 * Export Page View
 * صفحه نمایش فرم استخراج نظرات
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure logger class is loaded for log URL
if (!class_exists('WC_Reviews_Exporter_Logger')) {
    require_once WC_REVIEWS_EXPORTER_PLUGIN_DIR . 'includes/class-logger.php';
}

?>

<div class="wrap wc-reviews-exporter">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-download"></span>
        استخراج نظرات محصولات
    </h1>
    
    <hr class="wp-header-end">
    
    <?php
    // نمایش پیام‌های موفقیت یا خطا پس از ریدایرکت
    if (isset($_GET['export_success']) && $_GET['export_success'] == '1') {
        $filename = sanitize_text_field($_GET['filename']);
        $download_url = wc_reviews_exporter_get_csv_url() . $filename;
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>موفقیت!</strong> فایل CSV با موفقیت ایجاد شد.</p>';
        echo '<p><a href="' . esc_url($download_url) . '" class="button button-primary" download="' . esc_attr($filename) . '">دانلود فایل CSV</a></p>';
        echo '<p><a href="' . esc_url(WC_Reviews_Exporter_Logger::get_log_file_url()) . '" class="button button-secondary" target="_blank" download="wc-reviews-exporter.log">دانلود فایل لاگ برای عیب‌یابی</a></p>';
        echo '</div>';
    }
    
    if (isset($_GET['export_error'])) {
        $error_message = sanitize_text_field($_GET['export_error']);
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p><strong>خطا:</strong> ' . esc_html($error_message) . '</p>';
        echo '<p><a href="' . esc_url(WC_Reviews_Exporter_Logger::get_log_file_url()) . '" class="button button-secondary" target="_blank" download="wc-reviews-exporter.log">دانلود فایل لاگ برای عیب‌یابی</a></p>';
        echo '</div>';
    }
    ?>
    
    <div class="wc-reviews-export-form">
        <form method="post" action="">
            <?php wp_nonce_field('wc_reviews_export', 'wc_reviews_export_nonce'); ?>
            
            <div class="form-section">
                <h2>انتخاب محصولات</h2>
                <div class="product-selection-controls">
                    <button type="button" id="select-all-products" class="button button-secondary">انتخاب همه</button>
                    <button type="button" id="deselect-all-products" class="button button-secondary">لغو انتخاب همه</button>
                </div>
                
                <div class="products-list">
                    <?php if (!empty($products)) : ?>
                        <?php foreach ($products as $product) : ?>
                            <label class="product-item">
                                <input type="checkbox" name="products[]" value="<?php echo esc_attr($product->ID); ?>">
                                <span class="product-name"><?php echo esc_html($product->post_title); ?></span>
                                <span class="review-count">
                                    (<?php echo wc_reviews_exporter_persian_number($product->total_review_count); ?> نظر، 
                                    <?php echo wc_reviews_exporter_persian_number($product->pending_review_count); ?> منتظر تأیید)
                                </span>
                            </label>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="no-products">هیچ محصولی یافت نشد یا هیچ محصولی نظر تأیید شده‌ای ندارد.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-section">
                <h2>فیلتر تاریخ</h2>
                <div class="date-filter">
                    <div class="date-field">
                        <label for="date_from">از تاریخ (مثال: ۱۴۰۳/۰۳/۰۱ یا 1403/03/01):</label>
                        <input type="text" id="date_from" name="date_from" placeholder="تاریخ شروع به شمسی/میلادی">
                    </div>
                    <div class="date-field">
                        <label for="date_to">تا تاریخ (مثال: ۱۴۰۳/۰۳/۳۱ یا 1403/03/31):</label>
                        <input type="text" id="date_to" name="date_to" placeholder="تاریخ پایان به شمسی/میلادی">
                    </div>
                </div>
                <p class="description">
                    در صورت خالی بودن فیلدهای تاریخ، تمام نظرات بدون محدودیت زمانی استخراج خواهند شد. تاریخ را به فرمت **سال/ماه/روز** و با **اعداد فارسی یا انگلیسی** وارد کنید (مثال: ۱۴۰۳/۰۳/۱۵ یا 1403/03/15).
                </p>
            </div>
            
            <div class="form-section">
                <h2>تنظیمات استخراج</h2>
                <div class="export-options">
                    <label>
                        <input type="checkbox" name="include_pending" value="1">
                        شامل نظرات در انتظار تأیید نیز باشد
                    </label>
                </div>
            </div>
            
            <div class="form-actions">
                <input type="submit" name="export_reviews" value="استخراج CSV" class="button button-primary button-large">
                <p class="description">
                    <strong>توجه:</strong> در صورت وجود تعداد زیادی نظر، عملیات ممکن است چند دقیقه طول بکشد و هیچ نوار پیشرفتی نمایش داده نمی‌شود.
                </p>
            </div>
        </form>
    </div>
    
    <div class="export-info">
        <h3>اطلاعات فایل خروجی</h3>
        <ul>
            <li>فرمت فایل: CSV (با پشتیبانی کامل از UTF-8 و قابل باز شدن در نرم‌افزارهایی مانند Microsoft Excel)</li>
            <li>ستون‌های شامل: شناسه نظر، نام کاربر، ایمیل، محتوای نظر، امتیاز، تاریخ ثبت نظر، وضعیت نظر، نام محصول، IP کاربر</li>
            <li>تاریخ‌ها در فایل خروجی به صورت شمسی نمایش داده می‌شوند.</li>
            <li>فایل‌های CSV تولید شده پس از ۲۴ ساعت به صورت خودکار از سرور حذف می‌شوند تا فضای هاست اشغال نشود.</li>
            <li>تمام عملیات استخراج در فایل لاگ `wc-reviews-exporter.log` در پوشه آپلود وردپرس ثبت می‌شوند که برای عیب‌یابی مفید است. شما می‌توانید فایل لاگ را از <a href="<?php echo esc_url(WC_Reviews_Exporter_Logger::get_log_file_url()); ?>" target="_blank" download="wc-reviews-exporter.log">اینجا دانلود کنید</a>.</li>
        </ul>
    </div>
</div>

<style>

</style>