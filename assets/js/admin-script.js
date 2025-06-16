jQuery(document).ready(function($) {
    // منطق برای دکمه "انتخاب همه" محصولات
    $('#select-all-products').on('click', function() {
        $('.products-list input[type="checkbox"]').prop('checked', true);
    });

    // منطق برای دکمه "لغو انتخاب همه" محصولات
    $('#deselect-all-products').on('click', function() {
        $('.products-list input[type="checkbox"]').prop('checked', false);
    });

    // توجه: کد مربوط به فعال‌سازی Persian DatePicker از اینجا حذف شده است.
    // تاریخ‌ها اکنون به صورت دستی توسط کاربر وارد می‌شوند.
});
