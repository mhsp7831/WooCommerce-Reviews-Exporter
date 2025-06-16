# WooCommerce Reviews Exporter | صادرکننده نظرات ووکامرس

افزونه‌ای برای خروجی گرفتن نظرات محصولات ووکامرس به صورت فایل CSV با پشتیبانی از تاریخ شمسی.

A plugin for exporting WooCommerce product reviews to CSV format with Persian date support.

## امکانات | Features

- خروجی گرفتن نظرات محصولات ووکامرس به صورت CSV
- فیلتر کردن نظرات بر اساس بازه زمانی
- پشتیبانی از تاریخ شمسی در نام فایل و محتوای CSV
- مدیریت خودکار فایل‌های قدیمی (حذف خودکار پس از 24 ساعت)
- لاگ کردن عملیات‌ها برای عیب‌یابی

<br />

- Export WooCommerce product reviews to CSV
- Filter reviews by date range
- Persian date support in filenames and CSV content
- Automatic file management (24-hour cleanup)
- Operation logging for debugging

## پیش‌نیازها | Requirements

- وردپرس نسخه 5.0 یا بالاتر
- ووکامرس نسخه 3.0 یا بالاتر
- PHP نسخه 7.4 یا بالاتر

<br />

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.4 or higher

## نصب | Installation

1. فایل zip افزونه را از بخش [Releases](https://github.com/mhsp7831/wc-reviews-exporter/releases) دانلود کنید
2. به بخش افزونه‌های وردپرس بروید و روی "افزودن افزونه" کلیک کنید
3. روی "بارگذاری افزونه" کلیک کنید و فایل zip را انتخاب کنید
4. پس از نصب، روی "فعال کردن" کلیک کنید
5. به بخش "خروجی نظرات" در منوی وردپرس مراجعه کنید

<br />

1. Download the plugin zip file from [Releases](https://github.com/mhsp7831/WooCommerce-Reviews-Exporter/releases)
2. Go to WordPress plugins section and click "Add New"
3. Click "Upload Plugin" and select the zip file
4. After installation, click "Activate"
5. Navigate to "Reviews Export" in WordPress menu

## نحوه استفاده | Usage

1. به بخش "خروجی نظرات" در منوی وردپرس بروید
2. بازه زمانی مورد نظر را انتخاب کنید (اختیاری)
3. فیلدهای مورد نظر برای خروجی را انتخاب کنید
4. روی دکمه "خروجی CSV" کلیک کنید
5. فایل CSV با نام مناسب دانلود خواهد شد

<br />

1. Go to "Reviews Export" in WordPress menu
2. Select desired date range (optional)
3. Choose fields to export
4. Click "Export CSV" button
5. CSV file will be downloaded with appropriate name

## فرمت نام فایل | Filename Format

نام فایل‌های خروجی به صورت زیر خواهد بود:

- با انتخاب هر دو تاریخ: `reviews_1404-03-01_1404-03-30_14040326174508.csv`
- با انتخاب تاریخ شروع: `reviews_from_1404-03-01_14040326174553.csv`
- با انتخاب تاریخ پایان: `reviews_to_1404-03-30_14040326174623.csv`
- بدون انتخاب تاریخ: `reviews_all-time_14040326174623.csv`

<br />

Export filenames will be in the following format:

- With both dates: `reviews_1404-03-01_1404-03-30_14040326174508.csv`
- With start date: `reviews_from_1404-03-01_14040326174553.csv`
- With end date: `reviews_to_1404-03-30_14040326174623.csv`
- Without date: `reviews_all-time_14040326174623.csv`

## فیلدهای خروجی | Export Fields

- شناسه نظر | Review ID
- نام کاربر | User Name
- ایمیل کاربر | User Email
- امتیاز | Rating
- محتوای نظر | Review Content
- تاریخ و ساعت نظر (شمسی) | Review Date and Time (Persian)
- وضعیت نظر | Review Status

## امنیت | Security

- بررسی دسترسی‌های کاربر | User permission checks
- اعتبارسنجی داده‌های ورودی | Input validation
- محدودیت تعداد درخواست‌ها | Request rate limiting
- حذف خودکار فایل‌های موقت | Automatic temporary file cleanup

## عیب‌یابی | Troubleshooting

برای عیب‌یابی می‌توانید به فایل‌های لاگ در مسیر `wp-content/uploads/wc-reviews-exporter/logs` مراجعه کنید.

For troubleshooting, check the log files in `wp-content/uploads/wc-reviews-exporter/logs`.

## پشتیبانی | Support

برای گزارش مشکلات یا درخواست ویژگی‌های جدید، لطفاً از بخش Issues در گیت‌هاب استفاده کنید.

For bug reports or feature requests, please use the Issues section on GitHub.

## لایسنس | License

این افزونه تحت لایسنس GPL v2 یا بالاتر منتشر شده است.

This plugin is released under GPL v2 or later. 