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
/* استایل‌های عمومی و طرح‌بندی */
.wc-reviews-exporter {
    max-width: 1000px;
    margin-right: auto; /* برای RTL */
    margin-left: auto; /* برای RTL */
    font-family: 'Inter', sans-serif; /* فونت پیشنهادی */
    direction: rtl; /* پشتیبانی RTL */
    text-align: right; /* تنظیمات RTL */
}

.wc-reviews-export-form {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px; /* گوشه‌های گرد */
    padding: 25px;
    margin: 25px 0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05); /* سایه ملایم */
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section h2 {
    margin-top: 0;
    margin-bottom: 15px;
    color: #23282d;
    font-size: 20px;
    font-weight: 600;
}

/* کنترل‌های انتخاب محصول (همه/لغو همه) */
.product-selection-controls {
    margin-bottom: 15px;
    display: flex; /* برای قرارگیری دکمه‌ها کنار هم */
    gap: 10px; /* فاصله بین دکمه‌ها */
    justify-content: flex-end; /* دکمه‌ها به راست منتقل شوند در RTL */
}

.product-selection-controls .button {
    border-radius: 5px;
    padding: 8px 15px;
    height: auto;
    font-size: 14px;
}

/* لیست محصولات */
.products-list {
    max-height: 350px; /* ارتفاع ثابت با اسکرول */
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    background: #f9f9f9;
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.03);
}

.product-item {
    display: flex; /* برای قرارگیری المان‌ها در یک خط */
    align-items: center;
    margin-bottom: 8px;
    padding: 10px 12px;
    background: #fff;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.2s, box-shadow 0.2s;
    border: 1px solid #eee;
}

.product-item:hover {
    background: #f0f0f1;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.product-item input[type="checkbox"] {
    margin-left: 10px; /* فاصله چک‌باکس از متن در RTL */
    margin-right: 0;
    vertical-align: middle;
    transform: scale(1.2); /* بزرگ‌تر کردن چک‌باکس */
    accent-color: #007cba; /* رنگ آبی وردپرس */
}

.product-name {
    font-weight: 600;
    color: #333;
    flex-grow: 1; /* اجازه رشد به نام محصول */
}

.review-count {
    color: #777;
    font-size: 13px;
    white-space: nowrap; /* جلوگیری از شکستن خط */
    margin-right: 10px; /* فاصله از نام محصول */
}

/* فیلتر تاریخ */
.date-filter {
    display: flex;
    gap: 20px; /* فاصله بین فیلدهای تاریخ */
    flex-wrap: wrap; /* برای ریسپانسیو بودن در اندازه‌های کوچک */
    justify-content: flex-start; /* شروع از راست در RTL */
}

.date-field {
    flex: 1;
    min-width: 200px; /* حداقل عرض برای هر فیلد تاریخ */
}

.date-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #555;
}

/* برای ورودی‌های تاریخ دستی */
.date-filter input[type="text"] {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 15px;
    direction: ltr; /* برای نمایش صحیح تقویم و جایگیری متن */
    text-align: right; /* متن داخل فیلد به راست باشد */
    box-sizing: border-box; /* شامل padding و border در width */
}

.date-filter input[type="text"]::placeholder {
    color: #aaa;
}

.description {
    font-size: 13px;
    color: #777;
    margin-top: 10px;
    line-height: 1.6;
}

/* گزینه‌های استخراج (چک‌باکس) */
.export-options label {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    font-weight: 500;
    color: #333;
}

.export-options input[type="checkbox"] {
    margin-left: 10px; /* فاصله چک‌باکس از متن در RTL */
    margin-right: 0;
    transform: scale(1.2);
    accent-color: #007cba;
}

/* دکمه سابمیت */
.form-actions {
    text-align: center; /* دکمه در مرکز قرار گیرد */
    padding-top: 20px;
    border-top: 1px solid #eee;
    margin-top: 20px;
}

.form-actions .button-primary {
    padding: 12px 35px;
    height: auto;
    font-size: 17px;
    font-weight: 600;
    border-radius: 5px;
    background: #007cba; /* رنگ اصلی وردپرس */
    border-color: #007cba;
    box-shadow: 0 3px 5px rgba(0,0,0,0.1);
    transition: background-color 0.2s, border-color 0.2s, box-shadow 0.2s;
}

.form-actions .button-primary:hover {
    background: #006dae;
    border-color: #006dae;
    box-shadow: 0 5px 8px rgba(0,0,0,0.15);
}

/* اطلاعات فایل خروجی */
.export-info {
    background: #e9f5ff; /* پس‌زمینه آبی روشن */
    border: 1px solid #b3d4fc;
    border-radius: 8px;
    padding: 20px;
    margin-top: 25px;
    color: #333;
}

.export-info h3 {
    margin-top: 0;
    color: #0056b3; /* آبی پررنگ‌تر */
    font-size: 18px;
    font-weight: 600;
    border-bottom: 1px solid #cce5ff;
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.export-info ul {
    margin-bottom: 0;
    padding-right: 20px; /* فاصله از راست برای لیست */
}

.export-info li {
    margin-bottom: 8px;
    line-height: 1.6;
    list-style-type: disc; /* دایره‌های لیست */
    color: #444;
}

.no-products {
    text-align: center;
    color: #999;
    font-style: italic;
    padding: 20px;
    background: #fff;
    border-radius: 5px;
    border: 1px dashed #ddd;
}

/* پیام‌های وردپرس (Notice) */
.notice {
    margin: 15px 0;
    padding: 12px;
    border-radius: 4px;
    border-right: 4px solid; /* برای نمایش خط رنگی */
    direction: rtl;
    text-align: right;
}

.notice p {
    margin: 0.5em 0;
}

.notice-success {
    border-color: #46b450;
    background: #e9fbe9;
    color: #388e3c;
}

.notice-error {
    border-color: #dc3232;
    background: #ffebeb;
    color: #c00;
}

.notice .button-primary {
    margin-top: 10px;
}


/* ریسپانسیو برای موبایل */
@media (max-width: 768px) {
    .wc-reviews-exporter {
        padding: 0 10px;
    }

    .wc-reviews-export-form {
        padding: 15px;
    }

    .form-section h2 {
        font-size: 18px;
    }

    .product-selection-controls {
        flex-direction: column; /* دکمه‌ها زیر هم قرار گیرند */
        align-items: stretch; /* تمام عرض را بگیرند */
    }
    
    .product-selection-controls .button {
        width: 100%;
        margin-left: 0;
        margin-bottom: 10px;
    }

    .products-list {
        max-height: 250px;
    }

    .date-filter {
        flex-direction: column; /* فیلدهای تاریخ زیر هم قرار گیرند */
        gap: 15px;
    }

    .date-field {
        min-width: unset;
        width: 100%;
    }

    .form-actions .button-primary {
        width: 100%;
        padding: 10px 0;
    }

    .export-info {
        padding: 15px;
    }

    .export-info h3 {
        font-size: 16px;
    }
}
</style>