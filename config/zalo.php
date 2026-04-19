<?php
// =====================================================
// ZALO OA NOTIFICATION CONFIG
// =====================================================
// Lấy Access Token tại: https://oa.zalo.me → Cài đặt → Thông tin ứng dụng
// HƯỚNG DẪN: Thay YOUR_ZALO_OA_ACCESS_TOKEN bằng token thực của bạn
define('ZALO_OA_ACCESS_TOKEN', 'YOUR_ZALO_OA_ACCESS_TOKEN');

// App ID từ https://developers.zalo.me (dùng để refresh token nếu cần)
define('ZALO_APP_ID', 'YOUR_ZALO_APP_ID');

// Bật/tắt tính năng gửi Zalo (true = bật, false = tắt)
define('ZALO_NOTIFY_ENABLED', false); // ← Đổi thành true sau khi điền token thật
